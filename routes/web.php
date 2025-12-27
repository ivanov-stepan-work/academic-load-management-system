<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleMiddleware;

Route::middleware(['web'])->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::middleware(['role:1'])->group(function () {
    Route::get('/admin/{user}', function () {
        return view('welcome');
    });

    Route::get('/admin', function () {
        return view('welcome');
    });
});

Route::middleware(['role:2'])->group(function () {
    Route::get('/assistantDirector', function () {
        return view('welcome');
    });

    Route::get('/assistantDirector/{department}', function () {
        return view('welcome');
    });
});

Route::middleware(['role:3'])->group(function () {
    Route::get('/hoc', function () {
        return view('welcome');
    });

    Route::get('/hoc/{id}', function () {
        return view('welcome');
    });
});

Route::middleware(['role:4'])->group(function () {
    Route::get('/teacher', function () {
        return view('welcome');
    });

    Route::get('/teacher/{id}', function () {
        return view('welcome');
    });
});


Route::get('login', function () {
    return view('welcome');
});

Route::get('/user', function (Request $request) {
    return Auth::check() ? Auth::user() : 1;
});

Route::get('/', function () {
    return view('welcome');
});
