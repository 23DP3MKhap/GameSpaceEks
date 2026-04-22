<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\DatabaseController;
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

Route::get("/igdb/games", [GameController::class, 'getgames']);  

Route::post("/igdb/searchbyname", [GameController::class, 'getgamesbyname']);

// DATABASE

Route::middleware('auth:sanctum')->post("/database/addgame", [DatabaseController::class, 'addGame']);

Route::middleware('auth:sanctum')->post("/database/addreview", [DatabaseController::class, 'addReview']);

Route::get("/database/getreviews", [DatabaseController::class, 'getReviews']);

Route::middleware('auth:sanctum')->post("/database/addtocollection", [DatabaseController::class, 'addToCollection']);

Route::get("/database/getgenres", [DatabaseController::class, 'getGenres']);

Route::get("/database/getplatforms", [DatabaseController::class, 'getPlatforms']);

Route::get("/database/getgames", [DatabaseController::class, 'getGames']);

Route::get("/database/getgame", [DatabaseController::class, 'getGame']);

Route::get("/user/collection", [DatabaseController::class, 'getCollection']);

Route::get("/getuser", [AuthController::class, 'getuser']);

Route::middleware('auth:sanctum')->get("/database/checkcollection", [DatabaseController::class, 'checkCollection']);

Route::middleware('auth:sanctum')->post("/database/removefromcollection", [DatabaseController::class, 'removeFromCollection']);

Route::middleware('auth:sanctum')->post("/user/update", [DatabaseController::class, 'updateUser']);

Route::middleware('auth:sanctum')->post("/database/deletereview", [DatabaseController::class, 'deleteReview']);