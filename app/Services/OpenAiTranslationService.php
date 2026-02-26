<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAiTranslationService
{
    public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
    {
        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.translation_model', 'gpt-4o-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a translation assistant. Return only the translated text without explanations.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Translate the following text from {$sourceLocale} to {$targetLocale}:\n\n{$text}",
                        ],
                    ],
                    'temperature' => 0.1,
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $translated = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($translated)) {
            return null;
        }

        $normalized = trim($translated);

        return $normalized !== '' ? $normalized : null;
    }
}
