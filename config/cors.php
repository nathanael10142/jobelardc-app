<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/auth', // <== Nécessaire
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://jobelardc-app.onrender.com', // <== Ton front ici
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // <== Nécessaire pour envoyer les cookies
];
