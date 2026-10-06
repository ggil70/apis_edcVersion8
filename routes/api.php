<?php

use App\Http\Controllers\EstadoCuentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Estado de Cuenta Tarjeta de Crédito (Credicard)
|--------------------------------------------------------------------------
| Prefijo automático: /api
| Se usa POST para que el número de tarjeta viaje en el cuerpo y no en la URL
| (las URLs quedan registradas en logs de servidores y proxies).
*/

Route::prefix('v1/edc')
    ->middleware(['api.key', 'throttle:60,1'])
    ->controller(EstadoCuentaController::class)
    ->group(function () {
        Route::post('meses', 'meses');
        Route::post('movimientos', 'movimientos');
    });
