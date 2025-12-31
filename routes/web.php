<?php

use Grazulex\AutoBuilder\Http\Controllers\BrickController;
use Grazulex\AutoBuilder\Http\Controllers\ExecutionController;
use Grazulex\AutoBuilder\Http\Controllers\FlowController;
use Grazulex\AutoBuilder\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('autobuilder.routes.prefix', 'autobuilder'))
    ->middleware(config('autobuilder.routes.middleware', ['web', 'autobuilder.auth']))
    ->group(function () {

        // UI Routes
        Route::get('/', [FlowController::class, 'dashboard'])->name('autobuilder.index');
        Route::get('/flows/{flow}', [FlowController::class, 'edit'])->name('autobuilder.edit');

        // API Routes
        Route::prefix('api')->group(function () {

            // Flows CRUD
            Route::apiResource('flows', FlowController::class);
            Route::post('flows/{flow}/duplicate', [FlowController::class, 'duplicate'])->name('autobuilder.flows.duplicate');
            Route::post('flows/{flow}/activate', [FlowController::class, 'activate'])->name('autobuilder.flows.activate');
            Route::post('flows/{flow}/deactivate', [FlowController::class, 'deactivate'])->name('autobuilder.flows.deactivate');
            Route::get('flows/{flow}/validate', [FlowController::class, 'validate'])->name('autobuilder.flows.validate');
            Route::get('flows/{flow}/export', [FlowController::class, 'export'])->name('autobuilder.flows.export');
            Route::post('flows/import', [FlowController::class, 'import'])->name('autobuilder.flows.import');

            // Execution
            Route::post('flows/{flow}/test', [ExecutionController::class, 'test'])->name('autobuilder.flows.test');
            Route::post('flows/{flow}/run', [ExecutionController::class, 'run'])->name('autobuilder.flows.run');
            Route::get('flows/{flow}/runs', [ExecutionController::class, 'runs'])->name('autobuilder.flows.runs');
            Route::get('runs/{run}', [ExecutionController::class, 'show'])->name('autobuilder.runs.show');
            Route::get('runs/{run}/logs', [ExecutionController::class, 'logs'])->name('autobuilder.runs.logs');

            // Bricks
            Route::get('bricks', [BrickController::class, 'index'])->name('autobuilder.bricks.index');
            Route::get('bricks/categories', [BrickController::class, 'categories'])->name('autobuilder.bricks.categories');
            Route::get('bricks/{brick}/schema', [BrickController::class, 'schema'])->name('autobuilder.bricks.schema');
        });
    });

// Webhook Routes (public, no auth)
Route::prefix(config('autobuilder.routes.prefix', 'autobuilder'))
    ->group(function () {
        Route::any('webhook/{path}', [WebhookController::class, 'handle'])
            ->where('path', '.*')
            ->name('autobuilder.webhook');
    });
