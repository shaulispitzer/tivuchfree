<?php

namespace App\Listeners;

use App\Mail\WelcomeEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailOnVerification
{
    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        Mail::to($event->user)->send(new WelcomeEmail($event->user));
    }
}
