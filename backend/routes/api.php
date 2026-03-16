<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/ping', function () {
    return 'pinged';
});


Route::post('/register', [AuthController::class, 'register']);

Route::post('/emailcheck', [AuthController::class, 'emailcheck']);

Route::post('/usernamecheck', [AuthController::class, 'usernamecheck']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    try {
        return $request->user();
    } catch (error) {
        return ['message' => 'Not logged in'];
    }
});

