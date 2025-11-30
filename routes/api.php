<?php

use App\Http\Controllers\HoldController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('products/{id}', [ProductController::class, 'show']);
Route::post('hold', [HoldController::class, 'create']);
Route::post('order', [OrderController::class, 'createOrder']);