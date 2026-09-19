<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Verification;
use App\Models\VerificationFeeSetting;
use App\Support\VerificationQuestionnaire;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single home for verification review rules (M9.5). Both the API controller
 * (the app) and the admin web controller (the dashboard) go through this
 * service so status gating, reviewer recording, and badge issue can never
 * fork.
 *
 * On approval (M25.1) the badge is issued with the volunteer's name and photo
 * snapshotted, and the visit becomes a volunteer-signed blog story — the
 * story and its photos are what vouch for the listing, not the platform.
 */
class VerificationReviewService
{
    public function approve(Verification $verification, User $admin): Badge
    {
        if ($admin->role !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => ['Only admins can approve verifications.'],
            ]);
        }

        if ($verification->status !== Verification::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => ["A {$verification->status} report cannot be approved."],
            ]);
        }

        $volunteerName = $verification->volunteer?->user?->name ?? 'Unknown';
        $volunteerPhoto = $verification->volunteer?->photo_path;
        $feeInr = VerificationFeeSetting::current()->amount_inr;

        return DB::transaction(function () use ($verification, $admin, $volunteerName, $volunteerPhoto, $feeInr) {
            $verification->update([
                'status' => Verification::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
            ]);

            // The volunteer's story is the vouching artifact (M25.1).
            $post = $this->publishStory($verification, $volunteerName);

            return Badge::query()->create([
                'subject_type' => $verification->subject_type,
                'subject_id' => $verification->subject_id,
                'volunteer_name' => $volunteerName,
                'volunteer_photo' => $volunteerPhoto,
                'fee_inr' => $feeInr,
                'post_id' => $post->id,
                'issued_at' => now(),
            ]);
        });
    }

    public function reject(Verification $verification, User $admin): void
    {
        if ($admin->role !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => ['Only admins can reject verifications.'],
            ]);
        }

        if ($verification->status !== Verification::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => ["A {$verification->status} report cannot be rejected."],
            ]);
        }

        $verification->update([
            'status' => Verification::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
        ]);
    }

    /**
     * Publish the volunteer's site-visit story: their photos plus the answers
     * to the platform questionnaire. Authored by the volunteer.
     */
    private function publishStory(Verification $verification, string $volunteerName): Post
    {
        $vendor = $this->vendorFor($verification);
        $images = array_values((array) ($verification->evidence ?? []));
        $title = 'Verified: '.($vendor?->display_name ?? 'a seller').' — visited by '.$volunteerName;

        return Post::query()->create([
            'title' => $title,
            'slug' => Post::uniqueSlug($title),
            'excerpt' => 'A site visit by '.$volunteerName.', with photos and answers.',
            'body' => $this->storyBody($verification, $volunteerName),
            'cover_image' => $images[0] ?? null,
            'images' => $images,
            'vendor_id' => $vendor?->id,
            'author_id' => $verification->volunteer?->user_id,
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    private function vendorFor(Verification $verification): ?Vendor
    {
        if ($verification->subject_type === Vendor::class) {
            return Vendor::query()->find($verification->subject_id);
        }

        if ($verification->subject_type === Product::class) {
            return Product::query()->with('vendor')->find($verification->subject_id)?->vendor;
        }

        return null;
    }

    private function storyBody(Verification $verification, string $volunteerName): string
    {
        $lines = ['Site visit by '.$volunteerName.'.', ''];

        foreach (VerificationQuestionnaire::summarise($verification->checklist) as $line) {
            $lines[] = '- '.$line;
        }

        if ($verification->notes !== null && $verification->notes !== '') {
            $lines[] = '';
            $lines[] = 'Notes: '.$verification->notes;
        }

        $lines[] = '';
        $lines[] = 'Recorded by the volunteer who made the visit. The platform only shares what was recorded.';

        return implode("\n", $lines);
    }
}
