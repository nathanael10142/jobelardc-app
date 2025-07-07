<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/auth', // Nécessaire pour Echo (private/public channels)
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://jobelardc-app.onrender.com', // Ton frontend exact ici
    ],

    'allowed_origins_patterns' => [
        // Exemple si tu veux autoriser plusieurs sous-domaines
        // '^https:\/\/.*\.onrender\.com$',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-CSRF-TOKEN', // facultatif mais utile si tu veux le lire côté client
    ],

    'max_age' => 0,

    'supports_credentials' => true, // Crucial pour Sanctum + cookies cross-origin
];
