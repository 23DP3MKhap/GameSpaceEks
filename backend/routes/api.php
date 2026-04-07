<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// DATABASE CONNECTION

Route::get('/ping', function () {
    return 'pinged';
});


// LARAVEL AUTHENTICATION

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

Route::get("/user/email", [AuthController::class, 'getemail']);

Route::get("/user/username", [AuthController::class, 'getusername']);

Route::get("/user/id", [AuthController::class, 'getid']);


// IGDB API

Route::get("/igdb/client", [GameController::class, 'getclient']);
Route::get("/igdb/games", [GameController::class, 'getgames']);  // temporary route



