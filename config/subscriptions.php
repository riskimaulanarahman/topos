<?php

return [
    'plans' => [
        'monthly' => [
            'code' => 'monthly',
            'name' => 'Paket Bulanan',
            'description' => 'Semua paket memberikan akses penuh ke seluruh fitur yang tersedia di dalam aplikasi TOGA POS.',
            'price' => 50000,
            'duration' => 'P1M',
        ],
        'quarterly' => [
            'code' => 'quarterly',
            'name' => 'Paket 3 Bulan',
            'description' => 'Semua paket memberikan akses penuh ke seluruh fitur yang tersedia di dalam aplikasi TOGA POS.',
            'price' => 150000,
            'duration' => 'P3M',
            'promo_price' => 100000,
        ],
        'yearly' => [
            'code' => 'yearly',
            'name' => 'Paket Tahunan',
            'description' => 'Semua paket memberikan akses penuh ke seluruh fitur yang tersedia di dalam aplikasi TOGA POS.',
            'price' => 500000,
            'duration' => 'P1Y',
        ],
    ],
    'unique_code' => [
        'length' => 3,
        'min' => 100,
        'max' => 999,
    ],
    'proof_upload' => [
        'disk' => 'public',
        'directory' => 'payment-proofs',
        'max_size_kb' => 4096,
        'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
    ],
];
