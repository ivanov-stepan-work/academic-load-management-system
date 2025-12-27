<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TeacherStatusController;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DepartmentLoadController;
use App\Http\Controllers\Api\TeacherLoadController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\SubtypeController;
use App\Http\Controllers\Api\StudyYearController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AssistantDirectorController;
use App\Http\Controllers\Api\HocController;
use App\Http\Controllers\Api\TeacherController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::apiResource('roles', RoleController::class);
Route::apiResource('teacher_statuses', TeacherStatusController::class);
Route::apiResource('users', UserController::class);
Route::post('users/{user}/attach-roles-and-statuses', [UserController::class, 'attachRolesAndStatuses']);
Route::apiResource('departments', DepartmentController::class);
Route::apiResource('groups', GroupController::class);
Route::apiResource('specialties', SpecialtyController::class);
Route::apiResource('subtypes', SubtypeController::class);
Route::apiResource('study_years', StudyYearController::class);
Route::apiResource('subjects', SubjectController::class);
Route::apiResource('types', TypeController::class);
Route::apiResource('department_loads', DepartmentLoadController::class);
Route::apiResource('teacher_loads', TeacherLoadController::class);

Route::apiResource('admin', AdminController::class);
Route::apiResource('hoc', HocController::class);
Route::apiResource('assistantDirector', AssistantDirectorController::class);
Route::apiResource('teacher', TeacherController::class);
