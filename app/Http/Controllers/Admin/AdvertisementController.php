<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdvertisementStoreRequest;
use App\Http\Requests\AdvertisementUpdateRequest;
use App\Models\Advertisement;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdvertisementController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Advertisement::class);

        $advertisements = Advertisement::query()
            ->orderBy('position')
            ->get()
            ->map(fn (Advertisement $advertisement) => [
                'id' => $advertisement->id,
                'position' => $advertisement->position,
                'url' => $advertisement->url,
                'is_active' => $advertisement->is_active,
                'image_url' => $advertisement->imageUrl(),
            ]);

        return Inertia::render('admin/ads/Index', [
            'advertisements' => $advertisements,
            'can_create' => $advertisements->count() < Advertisement::MAX_COUNT,
            'max_count' => Advertisement::MAX_COUNT,
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $this->authorize('create', Advertisement::class);

        if (Advertisement::query()->count() >= Advertisement::MAX_COUNT) {
            return redirect()
                ->route('admin.ads.index')
                ->withErrors([
                    'advertisement' => 'You can only have up to '.Advertisement::MAX_COUNT.' advertisements.',
                ]);
        }

        return Inertia::render('admin/ads/Create', [
            'max_count' => Advertisement::MAX_COUNT,
            'taken_positions' => Advertisement::query()->pluck('position')->all(),
        ]);
    }

    public function store(AdvertisementStoreRequest $request): RedirectResponse
    {
        $path = $request->file('image')->store('ads', Advertisement::diskName());

        $advertisement = Advertisement::create([
            'position' => $request->integer('position'),
            'url' => $request->string('url')->toString(),
            'is_active' => $request->boolean('is_active'),
            'image_path' => $path,
        ]);

        return redirect()->route('admin.ads.edit', $advertisement)->success('Advertisement created successfully');
    }

    public function edit(Advertisement $advertisement): Response
    {
        $this->authorize('update', $advertisement);

        return Inertia::render('admin/ads/Edit', [
            'advertisement' => [
                'id' => $advertisement->id,
                'position' => $advertisement->position,
                'url' => $advertisement->url,
                'is_active' => $advertisement->is_active,
                'image_url' => $advertisement->imageUrl(),
            ],
            'max_count' => Advertisement::MAX_COUNT,
            'taken_positions' => Advertisement::query()
                ->whereKeyNot($advertisement->getKey())
                ->pluck('position')
                ->all(),
        ]);
    }

    public function update(AdvertisementUpdateRequest $request, Advertisement $advertisement): RedirectResponse
    {
        $data = [
            'position' => $request->integer('position'),
            'url' => $request->string('url')->toString(),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            $advertisement->deleteImage();
            $data['image_path'] = $request->file('image')->store('ads', Advertisement::diskName());
        }

        $advertisement->update($data);

        return redirect()->route('admin.ads.edit', $advertisement)->success('Advertisement updated successfully');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        $this->authorize('delete', $advertisement);

        $advertisement->deleteImage();
        $advertisement->delete();

        return redirect()->route('admin.ads.index')->success('Advertisement deleted successfully');
    }
}
