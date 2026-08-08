<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatAssistantController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\RbacController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onda 1 — Fundação (ver SIG-IIV-MEMORIA-PROJETO.md secção 15)
|--------------------------------------------------------------------------
| Fonte de verdade do contrato: Front-end/src/services/api/endpoints.ts.
| Caminhos e nomes ficam EXACTAMENTE como no frontend — nunca traduzir.
*/

// --- Auth (sem auth:sanctum, ver secção 2.2 da memória) ---------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// --- Rotas autenticadas -------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user/password', [AuthController::class, 'changePassword']);

    Route::post('/chat-assistant', [ChatAssistantController::class, 'stream']);

    Route::apiResource('departamentos', DepartmentController::class)
        ->middleware(['module:departamentos'])
        ->except(['store', 'update', 'destroy']);
    Route::post('/departamentos', [DepartmentController::class, 'store'])
        ->middleware('module:departamentos,write');
    Route::put('/departamentos/{department}', [DepartmentController::class, 'update'])
        ->middleware('module:departamentos,write');
    Route::delete('/departamentos/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('module:departamentos,write');

    Route::prefix('rbac')->group(function () {
        Route::get('/permissions', [RbacController::class, 'permissions'])->middleware('module:rbac');
        Route::put('/permissions', [RbacController::class, 'updatePermissions'])->middleware('module:rbac,write');
        Route::get('/users', [RbacController::class, 'users'])->middleware('module:rbac');
        Route::post('/users/{user}/roles', [RbacController::class, 'addRole'])->middleware('module:rbac,write');
        Route::delete('/users/{user}/roles/{role}', [RbacController::class, 'removeRole'])->middleware('module:rbac,write');
    });
});
