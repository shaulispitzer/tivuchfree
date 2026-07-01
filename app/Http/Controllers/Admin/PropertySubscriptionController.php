<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePropertySubscriptionRequest;
use App\Mail\PropertySubscriptionConfirmation;
use App\Models\PropertySubscription;
use App\Models\User;
use App\Services\PropertySubscriptionFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PropertySubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PropertySubscription::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $subscriptions = PropertySubscription::query()
            ->when($search !== '', fn ($query) => $query->where('email', 'like', '%'.$search.'%'))
            ->when($status === 'active', fn ($query) => $query->whereNull('unsubscribed_at'))
            ->when($status === 'unsubscribed', fn ($query) => $query->whereNotNull('unsubscribed_at'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (PropertySubscription $subscription) => [
                'id' => $subscription->id,
                'email' => $subscription->email,
                'is_active' => $subscription->unsubscribed_at === null,
                'subscribed_at' => $subscription->subscribed_at?->toDateTimeString(),
                'unsubscribed_at' => $subscription->unsubscribed_at?->toDateTimeString(),
                'update_filters_url' => route('subscriptions.update-filters', $subscription->token),
                'filters' => PropertySubscriptionFormData::filtersToFrontend($subscription->filters ?? []),
            ]);

        return Inertia::render('admin/subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => [
                'search' => $search,
                'status' => in_array($status, ['all', 'active', 'unsubscribed'], true) ? $status : 'all',
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PropertySubscription::class);

        return Inertia::render('admin/subscriptions/Create', PropertySubscriptionFormData::options());
    }

    public function store(StorePropertySubscriptionRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $filters = $request->filtersForStorage();

        $userId = User::query()->where('email', $email)->value('id');

        $existing = PropertySubscription::query()
            ->where('email', $email)
            ->whereNull('unsubscribed_at', 'and', false)
            ->first();

        if ($existing) {
            $existing->update([
                'filters' => $filters,
                'user_id' => $userId,
            ]);

            return redirect()
                ->route('admin.subscriptions.index')
                ->success('Subscription filters updated.');
        }

        $subscription = PropertySubscription::create([
            'email' => $email,
            'user_id' => $userId,
            'filters' => $filters,
            'token' => Str::random(64),
            'subscribed_at' => now(),
            'expires_at' => null,
        ]);

        Mail::to($subscription->email)->locale('en')->send(new PropertySubscriptionConfirmation(
            subscription: $subscription,
            unsubscribeUrl: route('subscriptions.unsubscribe', $subscription->token),
            updateFiltersUrl: route('subscriptions.update-filters', $subscription->token),
        ));

        return redirect()
            ->route('admin.subscriptions.index')
            ->success('Subscription created.');
    }

    public function destroy(PropertySubscription $propertySubscription): RedirectResponse
    {
        $this->authorize('delete', $propertySubscription);

        PropertySubscription::destroy($propertySubscription->getKey());

        return redirect()
            ->route('admin.subscriptions.index', [])
            ->success('Subscription deleted.');
    }

    public function unsubscribe(PropertySubscription $propertySubscription): RedirectResponse
    {
        $this->authorize('delete', $propertySubscription);

        if ($propertySubscription->unsubscribed_at === null) {
            $propertySubscription->update([
                'unsubscribed_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.subscriptions.index')
            ->success('Subscription unsubscribed.');
    }

    public function subscribe(PropertySubscription $propertySubscription): RedirectResponse
    {
        $this->authorize('delete', $propertySubscription);

        if ($propertySubscription->unsubscribed_at !== null) {
            $propertySubscription->update([
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        return redirect()
            ->route('admin.subscriptions.index')
            ->success('Subscription reactivated.');
    }
}
