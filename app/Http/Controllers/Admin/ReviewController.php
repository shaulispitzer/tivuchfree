<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(): Response
    {
        $reviews = Review::query()
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Review $review) => [
                'id' => $review->id,
                'name' => $review->name,
                'email' => $review->email,
                'role' => $review->role,
                'message' => $review->message,
                'created_at' => $review->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/Reviews', [
            'reviews' => $reviews,
        ]);
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->success('Review deleted successfully');
    }
}
