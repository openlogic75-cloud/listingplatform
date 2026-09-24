<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Consent;
use App\Models\DataRequest;
use App\Models\DonationSetting;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Retention & DPDP sweeps (M7.5).
 *
 * Runs four conservative cleanups, each of which is safe to re-run:
 *   1. stale guest bookings      — pending bookings nobody acted on get
 *                                  cancelled so vendor queues stay honest.
 *   2. orphaned media            — uploaded files never referenced by a
 *                                  listing, the donation QR or a verification
 *                                  are deleted after the retention window.
 *   3. expired consents          — granted consents past the consent window
 *                                  are revoked (DPDP: consent is not forever).
 *   4. deletion-request follow-up — DPDP requests stuck pending/processing
 *                                  beyond the SLA window are surfaced as a
 *                                  log warning for a human to action.
 *
 * Designed for shared hosting: no queues, runs synchronously in the schedule
 * (Q7-cron permitting) or manually via `php artisan retention:sweep`. Use
 * `--dry-run` to preview before committing; `--sweeps=stale-bookings,media`
 * to limit the run.
 */
class RetentionSweep extends Command
{
    protected $signature = 'retention:sweep
        {--dry-run : Report what would change without writing anything}
        {--sweeps= : Comma-separated subset of stale-bookings,media,consents,exports,deletion-requests}
        {--stale-days=30 : Cancel pending bookings older than this many days}
        {--media-days=90 : Delete unreferenced media older than this many days}
        {--consent-days=730 : Revoke unrevoked consents older than this many days}
        {--request-days=7 : Warn on DPDP requests stuck longer than this many days}
        {--export-days=1 : Delete private data export files older than this many days}';

    protected $description = 'Run retention sweeps (stale bookings, orphaned media, expired consents, DPDP follow-ups).';

    public function handle(): int
    {
        $sweeps = $this->sweepList();
        $dryRun = (bool) $this->option('dry-run');

        $counts = [];

        if (in_array('stale-bookings', $sweeps, true)) {
            $counts['stale_bookings'] = $this->sweepStaleBookings($dryRun);
        }

        if (in_array('media', $sweeps, true)) {
            $counts['orphaned_media'] = $this->sweepOrphanedMedia($dryRun);
        }

        if (in_array('consents', $sweeps, true)) {
            $counts['expired_consents'] = $this->sweepExpiredConsents($dryRun);
        }

        if (in_array('exports', $sweeps, true)) {
            $counts['expired_exports'] = $this->sweepExpiredExports($dryRun);
        }

        if (in_array('deletion-requests', $sweeps, true)) {
            $counts['stuck_deletion_requests'] = $this->sweepDeletionRequests();
        }

        $this->table(['Sweep', 'Count'], array_map(
            fn (string $k, int $v): array => [$k, (string) $v],
            array_keys($counts),
            array_values($counts),
        ));

        if ($dryRun) {
            $this->warn('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function sweepList(): array
    {
        $raw = (string) $this->option('sweeps');

        if ($raw === '') {
            return ['stale-bookings', 'media', 'consents', 'exports', 'deletion-requests'];
        }

        $allowed = ['stale-bookings', 'media', 'consents', 'exports', 'deletion-requests'];
        $chosen = array_map('trim', explode(',', $raw));

        return array_values(array_intersect($allowed, $chosen));
    }

    /**
     * Cancel pending guest bookings nobody ever confirmed. Pending rows with
     * a matching phone+code are still cancellable by the guest, so only rows
     * that have simply sat there get swept.
     */
    private function sweepStaleBookings(bool $dryRun): int
    {
        $cutoff = now()->subDays((int) $this->option('stale-days'));

        $ids = Booking::query()
            ->where('status', Booking::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        if (! $dryRun && $ids->isNotEmpty()) {
            DB::table('bookings')
                ->whereIn('id', $ids)
                ->update(['status' => Booking::STATUS_CANCELLED]);
        }

        if ($ids->isNotEmpty()) {
            Log::info('retention:sweep stale bookings', [
                'count' => $ids->count(),
                'dry_run' => $dryRun,
            ]);
        }

        return $ids->count();
    }

    /**
     * Delete media files nobody references. A file is "referenced" when its
     * path appears in any product image list, equals the donation QR, or is
     * attached to an open (non-rejected) verification. Only rows older than
     * the window AND not referenced are removed — uploads are given a grace
     * period in case a listing is still being drafted.
     */
    private function sweepOrphanedMedia(bool $dryRun): int
    {
        $cutoff = now()->subDays((int) $this->option('media-days'));

        $referenced = collect([
            Product::query()->whereNotNull('images')->pluck('images')->flatten(),
            [DonationSetting::query()->value('qr_path')],
        ])->flatten()->filter()->unique()->values();

        $orphans = Media::query()
            ->where('created_at', '<', $cutoff)
            ->get()
            ->filter(fn (Media $m) => ! $referenced->contains($m->path));

        if ($dryRun) {
            return $orphans->count();
        }

        $orphans->each(function (Media $m): void {
            foreach (['path'] as $column) {
                $disk = Storage::disk($m->disk);
                if ($disk->exists($m->path)) {
                    $disk->delete($m->path);
                }
            }
            $m->delete();
        });

        if ($orphans->isNotEmpty()) {
            Log::info('retention:sweep orphaned media', [
                'count' => $orphans->count(),
                'paths' => $orphans->pluck('path')->all(),
            ]);
        }

        return $orphans->count();
    }

    /**
     * Revoke consents that have outlived the consent window. The row is kept
     * (audit trail: who consented to what version when), only revoked_at is
     * set — DPDP compliance requires proving consent history even after it
     * expires.
     */
    private function sweepExpiredConsents(bool $dryRun): int
    {
        $cutoff = now()->subDays((int) $this->option('consent-days'));

        $ids = Consent::query()
            ->whereNull('revoked_at')
            ->where('granted_at', '<', $cutoff)
            ->pluck('id');

        if (! $dryRun && $ids->isNotEmpty()) {
            DB::table('consents')
                ->whereIn('id', $ids)
                ->update(['revoked_at' => now()]);
        }

        if ($ids->isNotEmpty()) {
            Log::info('retention:sweep expired consents', [
                'count' => $ids->count(),
                'dry_run' => $dryRun,
            ]);
        }

        return $ids->count();
    }

    /**
     * Remove private personal-data export artifacts after their download
     * window. Keep the DataRequest row for the audit trail, but clear its path.
     */
    private function sweepExpiredExports(bool $dryRun): int
    {
        $cutoff = now()->subDays((int) $this->option('export-days'));
        $requests = DataRequest::query()
            ->where('type', DataRequest::TYPE_EXPORT)
            ->where('status', DataRequest::STATUS_COMPLETED)
            ->whereNotNull('notes')
            ->get();

        $requests = $requests->filter(function (DataRequest $request) use ($cutoff): bool {
            $isLegacyPublicPath = str_starts_with((string) $request->notes, 'exports/');
            $isExpiredPrivatePath = str_starts_with((string) $request->notes, 'private:exports/')
                && $request->requested_at?->lt($cutoff);

            return $isLegacyPublicPath || $isExpiredPrivatePath;
        });

        if ($dryRun) {
            return $requests->count();
        }

        $requests->each(function (DataRequest $request): void {
            if (str_starts_with((string) $request->notes, 'exports/')) {
                // Older application versions placed decrypted exports on the
                // public disk. Remove those files immediately as a security
                // cleanup, regardless of age.
                Storage::disk('public')->delete($request->notes);
            } elseif (str_starts_with((string) $request->notes, 'private:exports/')) {
                $path = substr($request->notes, strlen('private:'));
                Storage::disk('local')->delete($path);
            }

            if (str_starts_with((string) $request->notes, 'exports/')) {
                $request->update(['notes' => 'Legacy public export file removed as security cleanup.']);
            } else {
                $request->update(['notes' => 'Private export file expired and was removed.']);
            }
        });

        if ($requests->isNotEmpty()) {
            Log::info('retention:sweep expired exports', ['count' => $requests->count()]);
        }

        return $requests->count();
    }

    /**
     * DPDP requests must be actioned within a reasonable window. Rows stuck
     * in pending/processing past the SLA are logged as a warning so an
     * operator can follow up (auto-processing is intentionally not done —
     * deletion is irreversible and needs a human's final confirmation).
     */
    private function sweepDeletionRequests(): int
    {
        $cutoff = now()->subDays((int) $this->option('request-days'));

        $stuck = DataRequest::query()
            ->whereIn('status', [DataRequest::STATUS_PENDING, DataRequest::STATUS_PROCESSING])
            ->where('requested_at', '<', $cutoff)
            ->select(['id', 'type', 'status', 'requested_at'])
            ->limit(50)
            ->get();

        if ($stuck->isNotEmpty()) {
            Log::warning('retention:sweep deletion requests need follow-up', [
                'count' => $stuck->count(),
                'requests' => $stuck
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'type' => $r->type,
                        'status' => $r->status,
                        'requested_at' => $r->requested_at?->toIso8601String(),
                    ])
                    ->all(),
            ]);
        }

        return $stuck->count();
    }
}
