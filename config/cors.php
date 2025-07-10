<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/auth',
    ],

    'allowed_methods' => ['*'], // Tous les types de requêtes (GET, POST, etc.)

    'allowed_origins' => [
        'http://127.0.0.1:3000',
        'http://localhost:3000',
        'http://127.0.0.1:8000',
        'http://localhost:8000',
        'https://jobelardc-app.onrender.com',
    ],

    'allowed_origins_patterns' => [
        // Optionnel : utile si tu veux autoriser plusieurs sous-domaines comme admin.example.com, etc.
        // '^https:\/\/.*\.onrender\.com$',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-CSRF-TOKEN', // Si tu veux accéder à ce header côté JS
    ],

    'max_age' => 0,

    'supports_credentials' => true, // ✅ Nécessaire pour Sanctum avec cookies
];
