<?php

use App\Jobs\ProcessNewPropertyListing;
use App\Jobs\SendEmailJob;
use App\Mail\YourPropertyWasListed;
use App\Models\Property;
use App\Models\User;
use App\Services\OpenAiTranslationService;
use App\Services\PropertyGeocoder;
use App\Services\PropertySubscriptionMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('geocodes the property, queues owner email, and translates additional info', function () {
    Queue::fake();

    config()->set('services.openai.api_key', 'test-openai-key');
    config()->set('services.openai.translation_model', 'gpt-4o-mini');

    mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')
            ->once()
            ->with('Test Street', '12')
            ->andReturn([
                'lat' => 31.8078717,
                'lon' => 35.2148620,
            ]);
    });

    /** @var User $user */
    $user = User::factory()->create();

    /** @var Property $property */
    $property = Property::factory()->create([
        'user_id' => $user->id,
        'street' => 'Test Street',
        'building_number' => 12,
        'lat' => null,
        'lon' => null,
        'additional_info' => [
            'en' => 'Bright apartment close to transit.',
            'he' => 'Bright apartment close to transit.',
        ],
    ]);

    mock(OpenAiTranslationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('translate')
            ->once()
            ->with('Bright apartment close to transit.', 'en', 'he')
            ->andReturn('דירה מוארת ליד תחבורה ציבורית');
    });

    mock(PropertySubscriptionMatcher::class, function (MockInterface $mock): void {
        $mock->shouldReceive('findMatchingSubscriptions')->once()->andReturn(collect());
    });

    $job = new ProcessNewPropertyListing(
        property: $property,
        street: 'Test Street',
        buildingNumber: '12',
        additionalInfo: 'Bright apartment close to transit.',
        sourceLocale: 'en',
    );

    $job->handle(
        app(PropertyGeocoder::class),
        app(PropertySubscriptionMatcher::class),
        app(OpenAiTranslationService::class),
    );

    $property->refresh();

    expect($property->lat)->toBe(31.8078717);
    expect($property->lon)->toBe(35.214862);
    expect($property->getTranslation('additional_info', 'he'))->toBe('דירה מוארת ליד תחבורה ציבורית');

    Queue::assertPushed(SendEmailJob::class, function (SendEmailJob $job) use ($user) {
        return $job->to === $user->email
            && $job->mailable instanceof YourPropertyWasListed;
    });
});

it('skips translation when additional info is empty', function () {
    Queue::fake();

    mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')->once()->andReturn(null);
    });

    mock(OpenAiTranslationService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('translate');
    });

    mock(PropertySubscriptionMatcher::class, function (MockInterface $mock): void {
        $mock->shouldReceive('findMatchingSubscriptions')->once()->andReturn(collect());
    });

    /** @var Property $property */
    $property = Property::factory()->create([
        'lat' => null,
        'lon' => null,
    ]);

    $job = new ProcessNewPropertyListing(
        property: $property,
        street: 'Test Street',
        buildingNumber: null,
        additionalInfo: null,
        sourceLocale: 'en',
    );

    $job->handle(
        app(PropertyGeocoder::class),
        app(PropertySubscriptionMatcher::class),
        app(OpenAiTranslationService::class),
    );
});
