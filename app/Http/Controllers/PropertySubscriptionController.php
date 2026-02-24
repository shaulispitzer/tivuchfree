<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertySubscriptionRequest;
use App\Http\Requests\UpdatePropertySubscriptionFiltersRequest;
use App\Http\Requests\VerifyPropertySubscriptionOtpRequest;
use App\Mail\PropertySubscriptionConfirmation;
use App\Mail\PropertySubscriptionOtp;
use App\Models\PropertySubscription;
use App\Models\PropertySubscriptionPending;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PropertySubscriptionController extends Controller
{
    public function create(): Response
    {
        $user = Auth::user();

        return Inertia::render('subscription/Subscribe', [
            'user' => $user !== null ? [
                'email' => $user->email,
            ] : null,
            'subscription_otp_pending' => session('subscription_otp_pending'),
            'neighbourhood_options' => array_map(
                fn ($n) => $n->value,
                \App\Enums\Neighbourhood::cases(),
            ),
            'furnished_options' => array_map(
                fn ($f) => ['value' => $f->value, 'label' => $f->label()],
                \App\Enums\PropertyFurnished::cases(),
            ),
            'type_options' => array_map(
                fn ($t) => ['value' => $t->value, 'label' => $t->label()],
                \App\Enums\PropertyLeaseType::cases(),
            ),
        ]);
    }

    public function store(StorePropertySubscriptionRequest $request): RedirectResponse
    {
        $email = $request->user()?->email ?? $request->validated('email');
        $filters = $request->filtersForStorage();

        $existing = PropertySubscription::query()
            ->where('email', $email)
            ->whereNull('unsubscribed_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            $existing->update(['filters' => $filters]);

            return redirect()->route('properties.index')->success(__('subscription.filtersUpdated'));
        }

        if ($request->user()) {
            $subscription = PropertySubscription::create([
                'email' => $email,
                'user_id' => $request->user()->id,
                'filters' => $filters,
                'token' => Str::random(64),
                'subscribed_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);

            Mail::to($subscription->email)->locale('en')->send(new PropertySubscriptionConfirmation(
                subscription: $subscription,
                unsubscribeUrl: route('subscriptions.unsubscribe', $subscription->token),
                updateFiltersUrl: route('subscriptions.update-filters', $subscription->token),
            ));

            return redirect()->route('properties.index')->success(__('subscription.subscribed'));
        }

        PropertySubscriptionPending::query()
            ->where('email', $email)
            ->delete();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PropertySubscriptionPending::create([
            'email' => $email,
            'filters' => $filters,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->locale('en')->send(new PropertySubscriptionOtp($email, $otp));

        return redirect()->route('subscribe')
            ->with('subscription_otp_pending', ['email' => $email])
            ->success(__('subscription.otpSent'));
    }

    public function verifyOtp(VerifyPropertySubscriptionOtpRequest $request): RedirectResponse
    {
        $pending = $request->getPendingSubscription();

        if (! $pending) {
            return back()->withErrors(['otp' => __('subscription.invalidOrExpiredOtp')]);
        }

        $existing = PropertySubscription::query()
            ->where('email', $pending->email)
            ->whereNull('unsubscribed_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            $existing->update(['filters' => $pending->filters]);
            $pending->delete();

            return redirect()->route('properties.index')->success(__('subscription.filtersUpdated'));
        }

        $subscription = PropertySubscription::create([
            'email' => $pending->email,
            'user_id' => null,
            'filters' => $pending->filters,
            'token' => Str::random(64),
            'subscribed_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $pending->delete();

        Mail::to($subscription->email)->locale('en')->send(new PropertySubscriptionConfirmation(
            subscription: $subscription,
            unsubscribeUrl: route('subscriptions.unsubscribe', $subscription->token),
            updateFiltersUrl: route('subscriptions.update-filters', $subscription->token),
        ));

        return redirect()->route('properties.index')->success(__('subscription.subscribed'));
    }

    public function unsubscribe(string $token): Response|RedirectResponse
    {
        $subscription = PropertySubscription::query()
            ->where('token', $token)
            ->whereNull('unsubscribed_at')
            ->firstOrFail();

        if (request()->has('confirm') && request()->boolean('confirm')) {
            $subscription->update(['unsubscribed_at' => now()]);

            return redirect()->route('properties.index')->success(__('subscription.unsubscribed'));
        }

        return Inertia::render('subscription/UnsubscribeConfirm', [
            'email' => $subscription->email,
            'token' => $token,
        ]);
    }

    public function confirmUnsubscribe(string $token): RedirectResponse
    {
        $subscription = PropertySubscription::query()
            ->where('token', $token)
            ->whereNull('unsubscribed_at')
            ->firstOrFail();

        $subscription->update(['unsubscribed_at' => now()]);

        return redirect()->route('properties.index')->success(__('subscription.unsubscribed'));
    }

    public function updateFilters(string $token): Response|RedirectResponse
    {
        $subscription = PropertySubscription::query()
            ->where('token', $token)
            ->whereNull('unsubscribed_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return Inertia::render('subscription/UpdateFilters', [
            'subscription' => [
                'token' => $subscription->token,
                'email' => $subscription->email,
                'filters' => $this->filtersToFrontend($subscription->filters),
            ],
            'neighbourhood_options' => array_map(
                fn ($n) => $n->value,
                \App\Enums\Neighbourhood::cases(),
            ),
            'furnished_options' => array_map(
                fn ($f) => ['value' => $f->value, 'label' => $f->label()],
                \App\Enums\PropertyFurnished::cases(),
            ),
            'type_options' => array_map(
                fn ($t) => ['value' => $t->value, 'label' => $t->label()],
                \App\Enums\PropertyLeaseType::cases(),
            ),
        ]);
    }

    public function saveFilters(UpdatePropertySubscriptionFiltersRequest $request, string $token): RedirectResponse
    {
        $subscription = PropertySubscription::query()
            ->where('token', $token)
            ->whereNull('unsubscribed_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $subscription->update(['filters' => $request->filtersForStorage()]);

        return redirect()->route('properties.index')->success(__('subscription.filtersUpdated'));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filtersToFrontend(array $filters): array
    {
        $min = $filters['bedrooms_min'] ?? 1;
        $max = $filters['bedrooms_max'] ?? 10;

        $neighbourhoods = $filters['neighbourhoods'] ?? null;
        if (is_array($neighbourhoods)) {
            $neighbourhoods = array_values($neighbourhoods);
        } else {
            $legacy = $filters['neighbourhood'] ?? null;
            $neighbourhoods = is_string($legacy) && $legacy !== '' ? [$legacy] : [];
        }

        return [
            'neighbourhoods' => $neighbourhoods,
            'hide_taken_properties' => ($filters['availability'] ?? 'all') === 'available',
            'bedrooms_range' => [min($min, $max), max($min, $max)],
            'furnished' => $filters['furnished'] ?? '',
            'type' => $filters['type'] ?? '',
            'available_from' => $filters['available_from'] ?? '',
            'available_to' => $filters['available_to'] ?? '',
        ];
    }
}
