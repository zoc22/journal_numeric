<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReadController;

Route::get('/', [ReadController::class, 'index'])->name('read');
