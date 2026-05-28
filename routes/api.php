<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;

Route::post('/tenants', [TenantController::class, 'store']);
