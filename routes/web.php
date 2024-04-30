<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceApiController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/data', [ServiceApiController::class, 'index']);