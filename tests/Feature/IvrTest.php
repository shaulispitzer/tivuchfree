<?php

use App\Models\Property;
use App\Support\Ivr;

function createIvrStreetProperties(array $streets): void
{
    foreach ($streets as $street) {
        Property::factory()->create(['street' => $street]);
    }
}

test('ivr index returns scrolling street read command', function () {
    createIvrStreetProperties(['עלי הכהן', 'רש"י', 'הרצל']);

    $response = $this->get(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=windows-1255')
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('עלי הכהן', 0)));
});

test('ivr digit 6 advances the scrolling street index', function () {
    createIvrStreetProperties(['עלי הכהן', 'רש"י', 'הרצל']);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 0,
        Ivr::READ_STEP_PARAM => 0,
        Ivr::digitParamForReadStep(0) => Ivr::DIGIT_NEXT,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('רש"י', 1, 1)));
});

test('ivr digit 4 moves to the previous scrolling street index', function () {
    createIvrStreetProperties(['עלי הכהן', 'רש"י', 'הרצל']);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 1,
        Ivr::READ_STEP_PARAM => 0,
        Ivr::digitParamForReadStep(0) => Ivr::DIGIT_PREVIOUS,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('עלי הכהן', 0, 1)));
});

test('ivr previous street wraps to the end of the scrolling street list', function () {
    createIvrStreetProperties(['עלי הכהן', 'רש"י', 'הרצל']);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 0,
        Ivr::READ_STEP_PARAM => 0,
        Ivr::digitParamForReadStep(0) => Ivr::DIGIT_PREVIOUS,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('הרצל', 2, 1)));
});

test('ivr tolerates yemot appending api params after property index', function () {
    createIvrStreetProperties(['עלי הכהן', 'רש"י', 'הרצל']);

    $response = $this->get('/ivr?property_index=1&read_step=0?ApiCallId=abc&digit_0=6');

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('הרצל', 2, 1)));
});

test('ivr uses the current read step digit and avoids reusing the digit parameter', function () {
    createIvrStreetProperties(['עלי הכהן', 'רש"י', 'הרצל']);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 1,
        Ivr::READ_STEP_PARAM => 3,
        Ivr::digitParamForReadStep(1) => Ivr::DIGIT_PREVIOUS,
        Ivr::digitParamForReadStep(3) => Ivr::DIGIT_NEXT,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('הרצל', 2, 4)));
});

test('ivr ignores properties without a street', function () {
    createIvrStreetProperties(['', 'הרצל']);

    $response = $this->get(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('הרצל', 0)));
});

test('ivr returns a read command when no streets exist', function () {
    $response = $this->get(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::readTextCommand('לא נמצאו רחובות', 0)));
});

test('ivr bypasses csrf', function () {
    createIvrStreetProperties(['עלי הכהן']);

    $response = $this->post(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingStreetReadCommand('עלי הכהן', 0)));
});
