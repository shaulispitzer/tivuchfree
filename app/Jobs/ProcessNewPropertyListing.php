<?php

namespace App\Jobs;

use App\Mail\YourPropertyWasListed;
use App\Models\Property;
use App\Services\OpenAiTranslationService;
use App\Services\PropertyGeocoder;
use App\Services\PropertySubscriptionMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessNewPropertyListing implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Property $property,
        public string $street,
        public ?string $buildingNumber = null,
        public ?string $additionalInfo = null,
        public string $sourceLocale = 'en',
    ) {}

    public function handle(
        PropertyGeocoder $geocoder,
        PropertySubscriptionMatcher $matcher,
        OpenAiTranslationService $translator,
    ): void {
        app()->call([new GeocodeProperty($this->property, $this->street, $this->buildingNumber), 'handle'], [
            'geocoder' => $geocoder,
        ]);

        $property = $this->property->fresh();

        if (! $property) {
            return;
        }

        $property->loadMissing('user');

        if ($property->user?->email) {
            SendEmailJob::dispatch(
                $property->user->email,
                new YourPropertyWasListed($property->user),
            );
        }

        app()->call([new NotifyPropertySubscribers($property), 'handle'], [
            'matcher' => $matcher,
        ]);

        if (is_string($this->additionalInfo) && $this->additionalInfo !== '') {
            app()->call([new TranslatePropertyAdditionalInfo($property, $this->additionalInfo, $this->sourceLocale), 'handle'], [
                'translator' => $translator,
            ]);
        }
    }
}
