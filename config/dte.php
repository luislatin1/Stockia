<?php

return [
    'mode' => env('DTE_MODE', 'simulacion'), // simulacion | real | contingencia
    'nit' => env('DTE_NIT'),
    'ambiente' => env('DTE_AMBIENTE', '00'), // 00 = pruebas, 01 = producción
    // Zona horaria fiscal: define el ejercicio impositivo y fecEmi/horEmi (Normativa 7.1.2).
    'timezone' => env('DTE_TIMEZONE', 'America/El_Salvador'),
    'api_user' => env('DTE_API_USER'),
    'api_password' => env('DTE_API_PASSWORD'),
    'auth_url' => env('DTE_AUTH_URL'),
    'send_url' => env('DTE_SEND_URL'),
    'signer_url' => env('DTE_SIGNER_URL'),
];
