<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\OpenAiTranslationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TranslatePropertyAdditionalInfo implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Property $property,
        public string $sourceText,
        public string $sourceLocale,
    ) {}

    public function handle(OpenAiTranslationService $translator): void
    {
        $targetLocale = $this->sourceLocale === 'he' ? 'en' : 'he';

        $translated = $translator->translate($this->sourceText, $this->sourceLocale, $targetLocale);

        if (! is_string($translated) || $translated === '') {
            return;
        }

        $property = $this->property->fresh();

        if (! $property) {
            return;
        }

        $property->setTranslation('additional_info', $targetLocale, $translated);
        $property->save();
    }
}
