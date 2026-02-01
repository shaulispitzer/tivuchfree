<?php

namespace App\Services;

use App\Enums\Locale;

class UserSettings
{
    public function _setLocale(Locale $locale): string
    {
        session()->put('locale', $locale->value);
        app()->setLocale($locale->value);
        $user = auth()->guard('web')->user();
        if ($user && data_get($user->preferences, 'locale') !== $locale->value) {
            $user->update([
                'preferences->locale' => $locale,
            ]);
        }

        return $locale->value;
    }

    public function _getLocale(): string
    {
        try {
            $locale = Locale::tryFrom(data_get(auth()->guard('web')->user()?->preferences, 'locale', request()->session()->get('locale'))) ? Locale::from(data_get(auth()->guard('web')->user()?->preferences, 'locale', request()->session()->get('locale'))) : null;
            if (! $locale) {
                $locale = Locale::EN;
            }
            if ($locale instanceof Locale) {
                $locale = $locale->value;
            }

            return $locale;
        } catch (\Throwable $th) {
            return Locale::EN->value;
        }
    }
}
