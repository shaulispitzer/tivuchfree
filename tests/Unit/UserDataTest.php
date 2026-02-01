<?php

use App\Data\UserData;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Tests\TestCase;

uses(TestCase::class);

test('user data accepts immutable created at dates', function () {
    $createdAt = CarbonImmutable::parse('2025-01-15 10:30:00');
    $user = User::factory()->make([
        'created_at' => $createdAt,
    ]);

    $data = UserData::fromModel($user);

    expect($data->created_at)
        ->toBeInstanceOf(CarbonInterface::class)
        ->and($data->created_at->equalTo($createdAt))->toBeTrue();
});
