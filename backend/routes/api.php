<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// PING

Route::get('/ping', function () {
    return 'pinged';
});


// LARAVEL AUTHENTICATION

Route::middleware('throttle:login')->post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('throttle:register')->post('/register', [AuthController::class, 'register']);

Route::middleware('throttle:emailcheck')->post('/emailcheck', [AuthController::class, 'emailcheck']);

Route::middleware('throttle:usernamecheck')->post('/usernamecheck', [AuthController::class, 'usernamecheck']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum', 'throttle:sendcode')->post('/email/sendcode', [AuthController::class, 'sendVerificationCode']);

Route::middleware('auth:sanctum', 'throttle:verifycode')->post('/email/verifycode', [AuthController::class, 'verifyCode']);


// IGDB API

Route::get("/igdb/games", [GameController::class, 'getgames']);

// DATABASE

Route::middleware('auth:sanctum', 'verified')->post("/database/addgame", [DatabaseController::class, 'addGame']);

Route::middleware('auth:sanctum', 'verified')->post("/database/addreview", [DatabaseController::class, 'addReview']);

Route::get("/database/getreviews", [DatabaseController::class, 'getReviews']);

Route::middleware('auth:sanctum', 'verified')->post("/database/addtocollection", [DatabaseController::class, 'addToCollection']);

Route::get("/database/getgenres", [DatabaseController::class, 'getGenres']);

Route::get("/database/getplatforms", [DatabaseController::class, 'getPlatforms']);

Route::get("/database/getgames", [DatabaseController::class, 'getGames']);

Route::get("/user/collection", [DatabaseController::class, 'getCollection']);

Route::get("/getuser", [AuthController::class, 'getuser']);


Route::middleware('auth:sanctum')->get("/database/checkcollection", [DatabaseController::class, 'checkCollection']);

Route::middleware('auth:sanctum')->post("/database/deleteuser", [DatabaseController::class, "deleteUser"]);

Route::middleware('auth:sanctum')->post("/database/removefromcollection", [DatabaseController::class, 'removeFromCollection']);

Route::middleware('auth:sanctum')->post("/user/update", [DatabaseController::class, 'updateUser']);

Route::middleware('auth:sanctum')->post("/database/deletereview", [DatabaseController::class, 'deleteReview']);

// ADMIN ROUTES

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'getUsers']);
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);

    Route::get('/admin/games', [AdminController::class, 'getGames']);
    Route::put('/admin/games/{id}', [AdminController::class, 'updateGame']);
    Route::delete('/admin/games/{id}', [AdminController::class, 'deleteGame']);

    Route::get('/admin/reviews', [AdminController::class, 'getReviews']);
    Route::delete('/admin/reviews/{id}', [AdminController::class, 'deleteReview']);

    Route::delete('/admin/users/{id}/collection', [AdminController::class, 'deleteUserCollection']);
});