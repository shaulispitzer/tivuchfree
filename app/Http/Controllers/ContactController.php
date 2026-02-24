<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {

        Mail::to('shaulispitzer@gmail.com')->locale('en')->send(new ContactMail(
            contactSubject: $request->validated('subject'),
            email: $request->validated('email'),
            isAboutDira: (bool) $request->validated('is_about_dira'),
            propertyId: $request->validated('property_id'),
            contactMessage: $request->validated('message'),
        ));

        return redirect()->route('home')->success('Message sent successfully!');
    }
}
