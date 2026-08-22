<?php

return [
    'vendor_kyc' => [
        'disk' => 'upload_quarantine',
        'max_size_bytes' => 10 * 1024 * 1024,
        'accepted_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
    ],

    'location_images' => [
        'disk' => 'private_files',
        'max_size_bytes' => 8 * 1024 * 1024,
        'accepted_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ],

    'scanner_provider' => env('FILE_SCANNER_PROVIDER', 'unavailable'),
];
