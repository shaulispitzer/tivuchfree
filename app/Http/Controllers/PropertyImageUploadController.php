<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyImageUploadStoreRequest;
use App\Models\TempUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PropertyImageUploadController extends Controller
{
    public function store(PropertyImageUploadStoreRequest $request): JsonResponse
    {
        $user = $request->user();

        $tempUpload = null;

        if ($request->filled('temp_upload_id')) {
            $tempUpload = TempUpload::query()
                ->whereKey($request->integer('temp_upload_id'))
                ->where('user_id', $user->id)
                ->first();

            if (! $tempUpload) {
                throw ValidationException::withMessages([
                    'temp_upload_id' => 'Invalid upload session.',
                ]);
            }
        } else {
            $tempUpload = TempUpload::create([
                'user_id' => $user->id,
            ]);
        }

        if ($tempUpload->getMedia('images')->count() >= 6) {
            throw ValidationException::withMessages([
                'image' => 'You can upload up to 6 images.',
            ]);
        }

        $media = $tempUpload
            ->addMediaFromRequest('image')
            ->toMediaCollection('images');

        return response()->json([
            'temp_upload_id' => $tempUpload->getKey(),
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
            ],
        ]);
    }

    public function destroy(Request $request, TempUpload $tempUpload, Media $media): JsonResponse
    {
        $user = $request->user();

        if (! $user || (int) $tempUpload->user_id !== (int) $user->id) {
            abort(404);
        }

        $belongsToTempUpload = $media->model_type === $tempUpload->getMorphClass()
            && (string) $media->model_id === (string) $tempUpload->getKey()
            && $media->collection_name === 'images';

        if (! $belongsToTempUpload) {
            abort(404);
        }

        $media->delete();

        if ($tempUpload->getMedia('images')->isEmpty()) {
            $tempUpload->delete();
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
