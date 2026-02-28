<?php

namespace App\Jobs;

use App\Mail\PropertySubscriptionExpired;
use App\Models\PropertySubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class ProcessExpiredPropertySubscriptions implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $expired = PropertySubscription::query()
            ->whereNull('unsubscribed_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $subscription) {
            Mail::to($subscription->email)->send(new PropertySubscriptionExpired(
                subscription: $subscription,
                subscribeUrl: route('subscribe'),
            ));

            $subscription->update(['unsubscribed_at' => now()]);
        }
    }
}
