<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;


Route::get('/', [UploadController::class, 'index']);
Route::post('/upload', [UploadController::class, 'store'])->name('upload');
Route::get('/uploads/json', [UploadController::class, 'uploadsJson'])->name('uploads.json');
