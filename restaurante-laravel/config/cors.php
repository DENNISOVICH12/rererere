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

    // ✅ Incluye tanto tu IP local como localhost para desarrollo
    'allowed_origins' => [
        'http://192.168.1.79:5174',
        'http://localhost:5174',
        'http://192.168.224.1:5174',
        'http://192.168.80.36:5174',
        'http://172.18.112.238:5174'
        

    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
