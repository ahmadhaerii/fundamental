<?php

use App\Http\Controllers\MyTest\MyTestController;
use Illuminate\Support\Facades\Route;
 Route::get('getData', [MyTestController::class, 'update'])->name('updateAllData');

//
//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
