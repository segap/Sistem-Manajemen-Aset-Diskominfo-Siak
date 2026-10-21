<?php

use App\Http\Controllers\Api\AssetQrController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
]));

Route::get('/publik/aset/{publicToken}', [AssetQrController::class, 'showPublic'])
    ->middleware('throttle:60,1')
    ->name('public.assets.show');
