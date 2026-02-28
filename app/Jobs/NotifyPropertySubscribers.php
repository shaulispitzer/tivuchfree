<?php

namespace App\Jobs;

use App\Mail\PropertySubscriptionNotification;
use App\Models\Property;
use App\Services\PropertySubscriptionMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifyPropertySubscribers implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Property $property,
    ) {}

    public function handle(PropertySubscriptionMatcher $matcher): void
    {
        $subscriptions = $matcher->findMatchingSubscriptions($this->property);
        $throttleSeconds = app()->environment('local')
            ? (int) config('mail.throttle_delay_seconds', 1)
            : 0;

        foreach ($subscriptions as $index => $subscription) {
            if ($throttleSeconds > 0 && $index > 0) {
                sleep($throttleSeconds);
            }

            Mail::to($subscription->email)->send(new PropertySubscriptionNotification(
                property: $this->property,
                subscription: $subscription,
                propertyUrl: route('properties.show', $this->property),
                unsubscribeUrl: route('subscriptions.unsubscribe', $subscription->token),
                updateFiltersUrl: route('subscriptions.update-filters', $subscription->token),
            ));
        }
    }
}
