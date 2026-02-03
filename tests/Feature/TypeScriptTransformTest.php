<?php

use Illuminate\Support\Facades\Artisan;

test('typescript transform outputs data and enums', function () {
    expect(Artisan::call('typescript:transform'))->toBe(0);

    $typesPath = resource_path('types/generated.d.ts');

    expect(file_exists($typesPath))->toBeTrue();

    $contents = file_get_contents($typesPath);

    expect($contents)->not->toBeFalse();
    expect($contents)->toContain('namespace App.Data');
    expect($contents)->toContain('PropertyData');
    expect($contents)->toContain('UserData');
    expect($contents)->toContain('namespace App.Enums');
    expect($contents)->toContain('PropertyLeaseType');
});
