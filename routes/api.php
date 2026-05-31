<?php

declare(strict_types=1);

use App\Http\Controllers\VendingMachineController;
use Illuminate\Support\Facades\Route;

// Customer-facing operations.
Route::post('coins', [VendingMachineController::class, 'insert']);
Route::post('coins/return', [VendingMachineController::class, 'returnCoins']);
Route::post('products/{selector}', [VendingMachineController::class, 'select']);

// Service operation (would sit behind auth in a real deployment).
Route::post('service', [VendingMachineController::class, 'service']);
