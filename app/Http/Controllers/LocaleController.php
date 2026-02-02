<?php

namespace App\Http\Controllers;

use App\Data\LocaleData;
use App\Enums\Locale;
use App\Services\UserSettings;

class LocaleController extends Controller
{
    public function __invoke(LocaleData $data)
    {
        $locale = $data->locale;
        if (! $locale) {
            $locale = Locale::HE;
        }

        app(UserSettings::class)->_setLocale($locale);

        return back()->success('locale changed successfully');
    }
}
