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
        $propertyIds = Property::query()
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($propertyIds->isEmpty()) {
            return Ivr::readTextCommand('לא נמצאו נכסים', 0);
        }

        $propertyIndex = $this->integerFromRequest($request, Ivr::PROPERTY_INDEX_PARAM);
        $digits = $this->digitsFromRequest($request);

        foreach ($digits as $digit) {
            if ($digit === Ivr::DIGIT_NEXT) {
                $propertyIndex++;
            }

            if ($digit === Ivr::DIGIT_PREVIOUS) {
                $propertyIndex--;
            }
        }

        $propertyIndex = ($propertyIndex % $propertyIds->count() + $propertyIds->count()) % $propertyIds->count();
        $nextReadStep = $digits === [] ? 0 : max(array_keys($digits)) + 1;

        return Ivr::scrollingPropertyIdReadCommand($propertyIds->get($propertyIndex), $propertyIndex, $nextReadStep);
    }

    protected function integerFromRequest(Request $request, string $key): int
    {
        $value = (string) $request->query($key, '0');

        preg_match('/^-?\d+/', $value, $matches);

        return (int) ($matches[0] ?? 0);
    }

    /**
     * @return array<int, string>
     */
    protected function digitsFromRequest(Request $request): array
    {
        $digits = [];

        foreach ($request->query() as $key => $value) {
            if (preg_match('/^'.preg_quote(Ivr::DIGIT_PARAM, '/').'_(\d+)$/', (string) $key, $matches) === 1) {
                $digits[(int) $matches[1]] = is_array($value) ? (string) end($value) : (string) $value;
            }
        }

        ksort($digits);

        if ($request->filled(Ivr::DIGIT_PARAM)) {
            $digit = $request->input(Ivr::DIGIT_PARAM);
            $digits[] = is_array($digit) ? (string) end($digit) : (string) $digit;
        }

        return $digits;
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
