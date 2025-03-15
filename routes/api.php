<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return response()->json([
        'message' => 'Fullstack Challenge 🏅 - Wallet Grupo Adriano Cobuccio'
    ]);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', action: [AuthController::class, 'logout']);


Route::middleware('auth:api')->group(function () {
    Route::post('wallet/deposit', action: [WalletController::class, 'deposit']);
    Route::post('wallet/transfer', [WalletController::class, 'transfer']);
    Route::post('wallet/reverse/{transactionId}', [WalletController::class, 'reverseTransaction']);
    Route::get('wallet/statement', [WalletController::class, 'statement']);
});
