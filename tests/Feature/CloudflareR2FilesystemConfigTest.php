<?php

it('configures the s3 disk for Cloudflare R2 compatibility', function () {
    $disk = config('filesystems.disks.s3');

    expect($disk)
        ->driver->toBe('s3')
        ->visibility->toBe('private')
        ->request_checksum_calculation->toBe('when_required')
        ->response_checksum_validation->toBe('when_required')
        ->region->toBe(env('AWS_DEFAULT_REGION', 'auto'));
});
