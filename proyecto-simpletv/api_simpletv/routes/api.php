<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ValidateUserController;
use App\Http\Controllers\GetBalanceController;
use App\Http\Controllers\PaidStvController;


use App\Http\Controllers\ValidarConsultaController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('/validateUser',[ValidateUserController::class,'validUser']);
Route::get('/getBalance',[GetBalanceController::class,'getMount']);
Route::get('/paidStv',[PaidStvController::class,'paidStv']);


//Api para validar el servicio si esta funcionando
Route::get('/validateConsulta',[ValidarConsultaController::class,'validConsulta']);

