<?php

use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;

Route::post('/mpesa/stk-callback', [MpesaController::class, 'stkCallback']);
Route::get('/mpesa/status', [MpesaController::class, 'checkStatus'])->middleware('auth');
