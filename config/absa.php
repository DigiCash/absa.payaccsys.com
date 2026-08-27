<?php
return [
    'environment' => env('ABSA_ENV', 'sandbox'),

    'statements' => [
        'base_url'      => env('ABSA_STATEMENTS_BASE_URL', env('ABSA_ENV') === 'production' ? 'https://api.absa.co.za/statements/v1' : 'https://sandbox.api.absa.co.za/statements/v1'),
        'api_key'       => env('ABSA_STATEMENTS_API_KEY'),
        'client_id'     => env('ABSA_STATEMENTS_CLIENT_ID'),
        'client_secret' => env('ABSA_STATEMENTS_CLIENT_SECRET'),
        'passphrase'    => env('ABSA_STATEMENTS_PASSPHRASE'),
        'cert_path'     => env('ABSA_STATEMENTS_CERT_PATH', storage_path('app/certs/absa/statements/client.pem')),
        'key_path'      => env('ABSA_STATEMENTS_SSL_KEY_PATH', storage_path('app/certs/absa/statements/client.key')),
    ],

    // Future placeholders for domain isolation
    'payshap' => [
        // 'base_url' => env('ABSA_PAYSHAP_BASE_URL'),
    ],
    'avs' => [
        // 'base_url' => env('ABSA_AVS_BASE_URL'),
    ],
];
