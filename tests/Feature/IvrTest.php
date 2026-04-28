<?php

use App\Models\Property;
use App\Support\Ivr;
use Illuminate\Database\Eloquent\Model;

function createIvrProperties(array $propertyIds): void
{
    foreach ($propertyIds as $propertyId) {
        Model::unguarded(fn () => Property::factory()->create(['id' => $propertyId]));
    }
}

test('ivr index returns scrolling property id read command', function () {
    createIvrProperties([9, 12, 20]);

    $response = $this->get(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=windows-1255')
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(9, 0)));
});

test('ivr digit 6 advances the scrolling property index', function () {
    createIvrProperties([9, 12, 20]);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 0,
        Ivr::READ_STEP_PARAM => 0,
        Ivr::digitParamForReadStep(0) => Ivr::DIGIT_NEXT,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(12, 1, 1)));
});

test('ivr advances from yemot fixed api link plus collected digit value', function () {
    createIvrProperties([9, 12, 20]);

    $response = $this->get('/ivr?property_index=0?ApiCallId=abc&digit_0=6');

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(12, 1, 1)));
});

test('ivr digit 4 moves to the previous scrolling property index', function () {
    createIvrProperties([9, 12, 20]);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 1,
        Ivr::READ_STEP_PARAM => 0,
        Ivr::digitParamForReadStep(0) => Ivr::DIGIT_PREVIOUS,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(9, 0, 1)));
});

test('ivr previous property wraps to the end of the scrolling property list', function () {
    createIvrProperties([9, 12, 20]);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 0,
        Ivr::READ_STEP_PARAM => 0,
        Ivr::digitParamForReadStep(0) => Ivr::DIGIT_PREVIOUS,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(20, 2, 1)));
});

test('ivr replays collected yemot digits from the fixed api link', function () {
    createIvrProperties([9, 12, 20]);

    $response = $this->get('/ivr?property_index=0?ApiCallId=abc&digit_0=6&digit_1=6');

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(20, 2, 2)));
});

test('ivr uses the current read step digit and avoids reusing the digit parameter', function () {
    createIvrProperties([9, 12, 20]);

    $response = $this->get(route('ivr.index', [
        Ivr::PROPERTY_INDEX_PARAM => 1,
        Ivr::READ_STEP_PARAM => 3,
        Ivr::digitParamForReadStep(1) => Ivr::DIGIT_PREVIOUS,
        Ivr::digitParamForReadStep(3) => Ivr::DIGIT_NEXT,
    ]));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(12, 1, 4)));
});

test('ivr includes properties without a street', function () {
    Model::unguarded(fn () => Property::factory()->create(['id' => 9, 'street' => '']));
    Model::unguarded(fn () => Property::factory()->create(['id' => 12, 'street' => 'הרצל']));

    $response = $this->get(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(9, 0)));
});

test('ivr returns a read command when no properties exist', function () {
    $response = $this->get(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::readTextCommand('לא נמצאו נכסים', 0)));
});

test('ivr bypasses csrf', function () {
    createIvrProperties([9]);

    $response = $this->post(route('ivr.index'));

    $response
        ->assertSuccessful()
        ->assertContent(iconv('UTF-8', 'windows-1255', Ivr::scrollingPropertyIdReadCommand(9, 0)));
});
