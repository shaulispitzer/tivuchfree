<?php

namespace App\Data\Shared;

use App\Data\UserData;
use App\Services\UserSettings;
use Closure;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

class SharedData extends Data
{
    public function __construct(
        #[TypeScriptType(UserData::class)]
        public ?Closure $user = null,
        public ?NotificationData $notification = null,
        public ?string $locale = null
    ) {
        $this->locale = app(UserSettings::class)->_getLocale();
        app()->setLocale($this->locale);

        $this->shareNotification();
    }

    protected function shareNotification(): void
    {
        if (session('notification')) {
            $this->notification = new NotificationData(
                ...session('notification')
            );
        }
    }
}
