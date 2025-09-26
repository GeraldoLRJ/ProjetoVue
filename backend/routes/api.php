<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserAdminController;

Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('tasks', TaskController::class);

    Route::group(['middleware' => ['role:admin,master']], function () {
        Route::apiResource('users', UserAdminController::class)->parameters([
            'users' => 'user'
        ]);
    });
});

// Companies CRUD (somente master)
Route::group(['middleware' => ['auth:api','role:master']], function () {
    Route::apiResource('companies', CompanyController::class);
});

