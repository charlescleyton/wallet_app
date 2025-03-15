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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', action: [AuthController::class, 'logout']);


Route::middleware('auth:api')->group(function () {
    Route::post('deposit', action: [WalletController::class, 'deposit']);
    Route::post('transfer', [WalletController::class, 'transfer']);
    Route::post('reverse/{transactionId}', [WalletController::class, 'reverseTransaction']);
    Route::get('statement', [WalletController::class, 'statement']);
});
