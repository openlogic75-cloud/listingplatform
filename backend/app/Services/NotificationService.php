<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DeviceToken;
use App\Models\Errand;
use App\Models\LogisticsJob;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Synchronous notification delivery (M8.1). Shared hosting has no queue
 * workers, so notifications are written to the database inbox immediately.
 * FCM HTTP push is wired behind the services.fcm_server_key config so it
 * can be enabled on the target plan without touching callers.
 */
class NotificationService
{
    private const CHANNEL_DATABASE = 'database';
    private const CHANNEL_FCM = 'fcm';

    /**
     * Notify a user of a booking status change (M3 lifecycle event).
     */
    public function bookingStatusChanged(Booking $booking, string $previousStatus): void
    {
        $vendorUser = $booking->vendor?->user;

        if ($vendorUser === null) {
            return;
        }

        $this->push($vendorUser, 'Booking '.$booking->code, "Status changed from {$previousStatus} to {$booking->status}.");
    }

    /**
     * Notify a driver they were assigned a pickup/delivery job.
     */
    public function jobAssigned(LogisticsJob $job): void
    {
        if ($job->driver_id === null) {
            return;
        }

        $driver = User::query()->find($job->driver_id);

        if ($driver === null) {
            return;
        }

        $this->push($driver, 'New '.$job->type.' job', 'A pickup/delivery job is waiting for you.');
    }

    /**
     * Notify a driver they were assigned an errand.
     */
    public function errandAssigned(Errand $errand): void
    {
        if ($errand->driver_id === null) {
            return;
        }

        $driver = User::query()->find($errand->driver_id);

        if ($driver === null) {
            return;
        }

        $this->push($driver, 'New errand', 'An errand request is waiting for you.');
    }

    /**
     * Notify a volunteer of a verification result.
     */
    public function verificationResult(Verification $verification, string $result): void
    {
        $volunteerUser = $verification->volunteer?->user;

        if ($volunteerUser === null) {
            return;
        }

        $this->push($volunteerUser, 'Verification '.$result, 'Your site-visit report was reviewed.');
    }

    /**
     * Write an in-app inbox notification and attempt an FCM push when
     * configured. Both are best-effort and never block the request.
     */
    public function push(User $user, string $title, string $body): void
    {
        try {
            $notification = new \App\Notifications\GenericNotification($title, $body);
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Database notification failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }

        $fcmKey = (string) config('services.fcm.server_key');
        if ($fcmKey === '') {
            return;
        }

        $this->pushFcm($user, $title, $body);
    }

    /**
     * FCM HTTP v1 push via the legacy send endpoint (token-based). Stores
     * nothing; failures are logged only.
     */
    private function pushFcm(User $user, string $title, string $body): void
    {
        $tokens = DeviceToken::query()->where('user_id', $user->id)->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $client = new \GuzzleHttp\Client(['timeout' => 3]);
        $fcmKey = (string) config('services.fcm.server_key');

        foreach ($tokens as $token) {
            try {
                $client->post('https://fcm.googleapis.com/fcm/send', [
                    'headers' => [
                        'Authorization' => 'key='.$fcmKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'to' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('FCM push failed', ['token' => $token, 'error' => $e->getMessage()]);
            }
        }
    }
}