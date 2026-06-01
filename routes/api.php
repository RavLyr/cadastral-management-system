<?php

use App\Http\Controllers\Api\GisApiController;
use App\Http\Controllers\Api\SismiopApiController;
use App\Http\Controllers\Api\TanahApiController;
use Illuminate\Support\Facades\Route;

Route::get('/gis/bidang', [GisApiController::class, 'bidang']);
Route::get('/gis/bidang/{nop}', [GisApiController::class, 'showBidang']);
Route::get('/gis/nop-status', [GisApiController::class, 'nopStatus']);

Route::get('/tanah/by-nop/{nop}', [TanahApiController::class, 'byNop']);
Route::get('/tanah/nop-list', [TanahApiController::class, 'nopList']);

Route::get('/sismiop/by-nop/{nop}', [SismiopApiController::class, 'byNop']);
