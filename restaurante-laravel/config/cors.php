<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de los permisos CORS para la API y la carta digital.
    | Esto permite que el frontend (Vue o Vite) pueda enviar peticiones
    | a tu backend Laravel sin ser bloqueado por el navegador.
    |
    */

    // ✅ Incluye tus rutas de API, Sanctum y endpoints públicos
    'paths' => ['api/*', 'orders', 'menu-items', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '/^https?:\/\/[^\/:]+(?::5180)?$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
