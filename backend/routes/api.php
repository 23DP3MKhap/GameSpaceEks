<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return 'pinged';
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/emailcheck', [AuthController::class, 'emailcheck']);

Route::post('/usernamecheck', [AuthController::class, 'usernamecheck']);

