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

        return response($body, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    protected function handleDefault(Request $request): string
    {
        $menu = $request->input(Ivr::READ_MENU_PARAM);

        if (filled($menu) && $menu !== Ivr::MENU_KEY_STREET) {
            return Ivr::idListMessageText('לא הוקשה בחירה או שהבחירה אינה זמינה');
        }

        return $this->streetTtsForDemoProperty();
    }

    /**
     * TTS: street name for property {@see Ivr::PROPERTY_ID_STREET_DEMO} (44).
     */
    protected function streetTtsForDemoProperty(): string
    {
        $property = Property::query()->find(Ivr::PROPERTY_ID_STREET_DEMO);

        if (! $property) {
            return Ivr::idListMessageText('הנכס לא נמצא');
        }

        $street = trim((string) $property->street);

        if ($street === '') {
            return Ivr::idListMessageText('אין שם רחוב בנכס');
        }

        return Ivr::idListMessageText('שם הרחוב הוא '.$street);
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

        return response($body, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
