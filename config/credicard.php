<?php

/*
|--------------------------------------------------------------------------
| Proveedor Credicard - Estado de cuenta de Tarjeta de Crédito
|--------------------------------------------------------------------------
| Equivalente a los Configure::write(...) del archivo core01.php original.
| Los valores reales se definen en el archivo .env del servidor.
*/

return [

    // Credicard_Api_Consultar_meses / Credicard_Api_Key_header_Consultar_meses
    'meses' => [
        'url'     => env('CREDICARD_API_CONSULTAR_MESES'),
        'api_key' => env('CREDICARD_API_KEY_CONSULTAR_MESES'),
    ],

    // Credicard_Api_Movimientos / Credicard_Api_Key_header_Movimientos
    'movimientos' => [
        'url'     => env('CREDICARD_API_MOVIMIENTOS'),
        'api_key' => env('CREDICARD_API_KEY_MOVIMIENTOS'),
    ],

    // Segundos máximos de espera de la respuesta del proveedor.
    'timeout' => (int) env('CREDICARD_TIMEOUT', 30),

    // Equivalente al "curl -k": false = no validar el certificado SSL del proveedor.
    'verificar_ssl' => filter_var(env('CREDICARD_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN),

    // Llave que deben enviar Postman / otros aplicativos en el header X-API-KEY
    // para consumir esta API. Si se deja vacía, la API queda abierta (no recomendado).
    'client_api_key' => env('API_EDC_CLIENT_KEY'),

];
