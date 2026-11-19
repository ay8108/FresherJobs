<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// URL trigger to run database migrations directly from browser without terminal access.
// ->withoutMiddleware('web') is critical here to bypass the session middleware so it won't
// fail if the 'sessions' table doesn't exist yet in the target database.
Route::get('/run-migration', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();

        return response(
            '<div style="font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #38bdf8; padding: 24px; border-radius: 8px; margin: 24px auto; max-width: 800px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">' .
            '<h2 style="color: #4ade80; margin-top: 0; font-size: 20px;">✓ Migrations Executed Successfully</h2>' .
            '<pre style="white-space: pre-wrap; word-break: break-all; color: #e2e8f0; background: #1e293b; padding: 16px; border-radius: 6px; font-size: 13px; line-height: 1.5;">' .
            e(trim($output) ?: 'Nothing to migrate. Database is already up to date.') .
            '</pre>' .
            '<p style="color: #94a3b8; margin: 16px 0 0 0; font-size: 14px;">Next step: Visit <a href="/" style="color: #60a5fa; text-decoration: underline; font-weight: bold;">Home Page</a>.</p>' .
            '</div>'
        );
    } catch (\Throwable $e) {
        return response(
            '<div style="font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #f87171; padding: 24px; border-radius: 8px; margin: 24px auto; max-width: 800px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">' .
            '<h2 style="color: #ef4444; margin-top: 0; font-size: 20px;">✗ Migration Failed</h2>' .
            '<pre style="white-space: pre-wrap; word-break: break-all; color: #fca5a5; background: #1e293b; padding: 16px; border-radius: 6px; font-size: 13px; line-height: 1.5;">' .
            e($e->getMessage()) .
            '</pre>' .
            '</div>',
            500
        );
    }
})->withoutMiddleware('web');

// Migration status route
Route::get('/migration-status', function () {
    try {
        Artisan::call('migrate:status');
        $output = Artisan::output();

        return response(
            '<div style="font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #38bdf8; padding: 24px; border-radius: 8px; margin: 24px auto; max-width: 800px;">' .
            '<h2 style="color: #60a5fa; margin-top: 0; font-size: 20px;">Migration Status</h2>' .
            '<pre style="white-space: pre-wrap; word-break: break-all; color: #e2e8f0; background: #1e293b; padding: 16px; border-radius: 6px; font-size: 13px; line-height: 1.5;">' .
            e(trim($output) ?: 'No migration status output.') .
            '</pre>' .
            '</div>'
        );
    } catch (\Throwable $e) {
        return response(
            '<pre style="color: red; padding: 20px;">' . e($e->getMessage()) . '</pre>',
            500
        );
    }
})->withoutMiddleware('web');

// Cache & config clear helper route
Route::get('/optimize-clear', function () {
    try {
        Artisan::call('optimize:clear');
        $output = Artisan::output();

        return response(
            '<div style="font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #38bdf8; padding: 24px; border-radius: 8px; margin: 24px auto; max-width: 800px;">' .
            '<h2 style="color: #4ade80; margin-top: 0; font-size: 20px;">✓ Optimize Clear Executed</h2>' .
            '<pre style="white-space: pre-wrap; word-break: break-all; color: #e2e8f0; background: #1e293b; padding: 16px; border-radius: 6px; font-size: 13px; line-height: 1.5;">' .
            e(trim($output) ?: 'Optimization cache cleared.') .
            '</pre>' .
            '</div>'
        );
    } catch (\Throwable $e) {
        return response(
            '<pre style="color: red; padding: 20px;">' . e($e->getMessage()) . '</pre>',
            500
        );
    }
})->withoutMiddleware('web');
