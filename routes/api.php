<?php

use App\Http\Controllers\MpesaController;
use App\Http\Controllers\UssdController;
use Illuminate\Support\Facades\Route;

Route::post('/mpesa/stk-callback', [MpesaController::class, 'stkCallback']);
Route::get('/mpesa/status', [MpesaController::class, 'checkStatus'])->middleware('auth');

// Africa's Talking USSD callback. In api.php on purpose: no CSRF, since AT posts directly.
Route::post('/ussd', [UssdController::class, 'handle']);