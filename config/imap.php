<?php

return [
    'default' => 'default',
    
    'accounts' => [
        'default' => [
            'host' => env('IMAP_HOST', 'imap.gmail.com'),
            'port' => env('IMAP_PORT', 993),
            'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
            'username' => env('IMAP_USERNAME'),
            'password' => env('IMAP_PASSWORD'),
            'protocol' => env('IMAP_PROTOCOL', 'imap'),
            // ✅ SSL sertifika doğrulama hatası için
            'options' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
        ],
    ],
];
