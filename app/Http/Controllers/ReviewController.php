<?php

namespace App\Http\Controllers;

use App\Data\Forms\ReviewFormData;
use App\Mail\ReviewMail;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ReviewController extends Controller
{
    public function store(ReviewFormData $data): RedirectResponse
    {
        $review = Review::create($data->toArray());

        Mail::to('info@tivuchfree.com')->locale('en')->send(new ReviewMail(
            reviewerName: $review->name,
            reviewerEmail: $review->email,
            reviewerRole: $review->role,
            reviewMessage: $review->message,
        ));

        return back()->success(__('message.reviewSubmittedSuccessfully'));
    }
}
