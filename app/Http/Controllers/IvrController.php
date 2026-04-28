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

        $propertyIndex = $this->integerFromRequest($request, Ivr::PROPERTY_INDEX_PARAM);
        $readStep = $this->integerFromRequest($request, Ivr::READ_STEP_PARAM);
        $digit = $this->digitFromRequest($request, $readStep);

        if ($digit === Ivr::DIGIT_NEXT) {
            $propertyIndex++;
        }

        if ($digit === Ivr::DIGIT_PREVIOUS) {
            $propertyIndex--;
        }

        $propertyIndex = ($propertyIndex % $streets->count() + $streets->count()) % $streets->count();
        $nextReadStep = filled($digit) ? $readStep + 1 : $readStep;

        return Ivr::scrollingStreetReadCommand($streets->get($propertyIndex), $propertyIndex, $nextReadStep);
    }

    protected function integerFromRequest(Request $request, string $key): int
    {
        $value = (string) $request->query($key, '0');

        preg_match('/^-?\d+/', $value, $matches);

        return (int) ($matches[0] ?? 0);
    }

    protected function digitFromRequest(Request $request, int $readStep): ?string
    {
        $digitParam = Ivr::digitParamForReadStep($readStep);

        if ($request->filled($digitParam)) {
            return (string) $request->input($digitParam);
        }

        if ($request->filled(Ivr::DIGIT_PARAM)) {
            return (string) $request->input(Ivr::DIGIT_PARAM);
        }

        foreach ($request->query() as $key => $value) {
            if (preg_match('/^'.preg_quote(Ivr::DIGIT_PARAM, '/').'_\d+$/', (string) $key) === 1) {
                return is_array($value) ? (string) end($value) : (string) $value;
            }
        }

        return null;
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
