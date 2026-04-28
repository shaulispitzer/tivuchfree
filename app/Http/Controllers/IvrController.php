<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Support\Ivr;
use App\Support\YemotApi;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IvrController extends Controller
{
    /**
     * Handle incoming IVR requests from Yemot Hamashiach.
     */
    public function __invoke(Request $request)
    {
        $action = $request->query('action');

        $body = match ($action) {
            default => $this->handleDefault($request),
        };

        return $this->ivrResponse($body);
    }

    protected function handleDefault(Request $request): string
    {
        $streets = Property::query()
            ->whereNotNull('street')
            ->where('street', '<>', '')
            ->orderBy('id')
            ->pluck('street')
            ->map(fn (string $street): string => trim($street))
            ->filter()
            ->values();

        if ($streets->isEmpty()) {
            return Ivr::readTextCommand('לא נמצאו רחובות', 0);
        }

        $propertyIndex = $request->integer(Ivr::PROPERTY_INDEX_PARAM, 0);
        $digit = $request->input(Ivr::DIGIT_PARAM);

        if ($digit === Ivr::DIGIT_NEXT) {
            $propertyIndex++;
        }

        if ($digit === Ivr::DIGIT_PREVIOUS) {
            $propertyIndex--;
        }

        $propertyIndex = ($propertyIndex % $streets->count() + $streets->count()) % $streets->count();

        return Ivr::scrollingStreetReadCommand($streets->get($propertyIndex), $propertyIndex);
    }

    /**
     * Delete a file (recording) from the IVR filesystem.
     */
    public function deleteFile(Request $request): Response
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $request->input('path');

        $response = (new YemotApi)->deleteFile($path);

        logger('deleteFile', [$path, $response->json()]);

        $body = $response->json('success')
            ? 'id_list_message=f-'.Ivr::getMessagePath('file_deleted')
            : 'id_list_message=f-'.Ivr::getMessagePath('error');

        return $this->ivrResponse($body);
    }

    protected function ivrResponse(string $body): Response
    {
        return response(iconv('UTF-8', 'windows-1255', $body), Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=windows-1255',
        ]);
    }
}
