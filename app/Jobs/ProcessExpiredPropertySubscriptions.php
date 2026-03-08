<?php

namespace App\Jobs;

use App\Mail\PropertySubscriptionExpired;
use App\Models\PropertySubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessExpiredPropertySubscriptions implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Subscriptions are now permanent — expiry processing disabled.
        // To re-enable 30-day expiry, uncomment the block below:
        //
        // $expired = PropertySubscription::query()
        //     ->whereNull('unsubscribed_at')
        //     ->where('expires_at', '<=', now())
        //     ->get();
        //
        // foreach ($expired as $index => $subscription) {
        //     // Stagger dispatch at 1 email/second, leaving headroom for synchronous sends (e.g. OTP emails).
        //     $delaySeconds = $index;
        //
        //     SendEmailJob::dispatch(
        //         $subscription->email,
        //         new PropertySubscriptionExpired(
        //             subscription: $subscription,
        //             subscribeUrl: route('subscribe'),
        //         ),
        //     )->delay(now()->addSeconds($delaySeconds));
        //
        //     $subscription->update(['unsubscribed_at' => now()]);
        // }
    }
}
