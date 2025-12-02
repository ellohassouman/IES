<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GlobalController;

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

Route::post('GetUserBLPerNumber', [GlobalController::class, 'GetUserBLPerNumber']);
Route::post('GetUserBLHistory', [GlobalController::class, 'GetUserBLHistory']);
Route::post('GetDetailsPerBLNumber', [GlobalController::class, 'GetDetailsPerBLNumber']);
Route::post('GetInvoicesPerBLNumber', [GlobalController::class, 'GetInvoicesPerBLNumber']);
Route::post('GetPendingInvoicingItemsPerBLNumber', [GlobalController::class, 'GetPendingInvoicingItemsPerBLNumber']);
