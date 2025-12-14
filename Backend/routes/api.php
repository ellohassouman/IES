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
Route::post('GetYardItemsPerBLNumber', [GlobalController::class, 'GetYardItemsPerBLNumber']);
Route::post('GetYardItemTrackingMovements', [GlobalController::class, 'GetYardItemTrackingMovements']);
Route::post('DeleteYardItemEvent', [GlobalController::class, 'DeleteYardItemEvent']);
Route::post('SearchBLByNumber', [GlobalController::class, 'SearchBLByNumber']);
Route::post('GetBLByNumber', [GlobalController::class, 'GetBLByNumber']);
Route::post('GetCartByUserId', [GlobalController::class, 'GetCartByUserId']);
Route::post('AddInvoiceToCart', [GlobalController::class, 'AddInvoiceToCart']);
Route::post('GetCurrentUserCartCount', [GlobalController::class, 'GetCurrentUserCartCount']);
Route::post('RemoveInvoiceFromCart', [GlobalController::class, 'RemoveInvoiceFromCart']);
Route::post('UpdateInvoiceStatus', [GlobalController::class, 'UpdateInvoiceStatus']);
Route::post('DeleteInvoice', [GlobalController::class, 'DeleteInvoice']);
Route::post('Login', [GlobalController::class, 'Login']);
Route::post('CreateProforma', [GlobalController::class, 'CreateProforma']);
Route::post('GenerateProformaWithBillingDate', [GlobalController::class, 'CreateProforma']);
Route::post('AddYardItemEvent', [GlobalController::class, 'AddYardItemEvent']);
