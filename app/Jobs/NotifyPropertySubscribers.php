<?php

namespace App\Jobs;

use App\Mail\PropertySubscriptionNotification;
use App\Models\Property;
use App\Services\PropertySubscriptionMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyPropertySubscribers implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Property $property,
    ) {}

    public function handle(PropertySubscriptionMatcher $matcher): void
    {
        $subscriptions = $matcher->findMatchingSubscriptions($this->property);

        foreach ($subscriptions as $index => $subscription) {
            // Stagger dispatch at 1 email/second, leaving headroom for synchronous sends (e.g. OTP emails).
            $delaySeconds = $index;

            SendEmailJob::dispatch(
                $subscription->email,
                new PropertySubscriptionNotification(
                    property: $this->property,
                    subscription: $subscription,
                    propertyUrl: route('properties.show', $this->property),
                    unsubscribeUrl: route('subscriptions.unsubscribe', $subscription->token),
                    updateFiltersUrl: route('subscriptions.update-filters', $subscription->token),
                ),
            )->delay(now()->addSeconds($delaySeconds));
        }
    }
}
