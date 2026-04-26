<?php

use App\Models\Property;
use App\Models\User;
use App\Support\Ivr;
use Illuminate\Database\Eloquent\Model;

test('ivr returns read prompt for single digit then welcome file', function () {
    $response = $this->get(route('ivr.index'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee(Ivr::readWelcomeThenMenuDigit(), false);
});

test('ivr menu key 2 returns street TTS for property 44', function () {
    $user = User::factory()->create();
    $attributes = Property::factory()->for($user)->make(['street' => 'הרצל'])->getAttributes();
    Model::unguarded(fn () => Property::query()->create(array_merge($attributes, ['id' => 44])));

    $response = $this->get(route('ivr.index', [Ivr::READ_MENU_PARAM => Ivr::MENU_KEY_STREET]));

    $response->assertStatus(200);
    $expected = Ivr::idListMessageText('שם הרחוב הוא הרצל');
    $response->assertSee($expected, false);
});

test('ivr menu key 2 when property 44 is missing says not found', function () {
    $response = $this->get(route('ivr.index', [Ivr::READ_MENU_PARAM => Ivr::MENU_KEY_STREET]));

    $response->assertStatus(200);
    $response->assertSee(Ivr::idListMessageText('הנכס לא נמצא'), false);
});

test('ivr bypasses csrf', function () {
    $response = $this->post(route('ivr.index'));

    $response->assertStatus(200);
    $response->assertSee('read=', false);
});
