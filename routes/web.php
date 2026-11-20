<?php

use App\Http\Controllers\CommandRunnerController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// URL trigger to run database migrations directly from browser without terminal access.
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

// URL trigger to run jobs:scrape-all (all spiders + automatic AI classification)
Route::get('/run-scrape', function () {
    set_time_limit(0);
    ignore_user_abort(true);

    return response()->stream(function () {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_implicit_flush(true);

        echo '<!DOCTYPE html><html><head><title>Scraping Jobs</title>' .
             '<style>body{background:#0f172a;color:#e2e8f0;font-family:monospace;padding:24px;font-size:13px;line-height:1.6;max-width:900px;margin:20px auto;}' .
             'h2{color:#38bdf8;margin-top:0;}pre{background:#1e293b;padding:16px;border-radius:6px;white-space:pre-wrap;word-break:break-all;}</style></head><body>';
        echo '<h2>🚀 Starting jobs:scrape-all ...</h2>';
        echo '<p style="color:#94a3b8;">Please wait. Running all 10 spiders followed by AI classification (this may take 1-2 minutes)...</p>';
        echo '<pre>';
        flush();

        try {
            Artisan::call('jobs:scrape-all');
            echo e(Artisan::output());
            echo '</pre><h3 style="color:#4ade80;">✓ All Scraping and Classification Finished!</h3>';
            echo '<p><a href="/" style="color:#60a5fa;font-weight:bold;text-decoration:underline;">← Back to Home Page</a></p>';
        } catch (\Throwable $e) {
            echo '</pre><h3 style="color:#ef4444;">✗ Scraping Failed: ' . e($e->getMessage()) . '</h3>';
        }

        echo '</body></html>';
        flush();
    }, 200, [
        'Content-Type' => 'text/html; charset=utf-8',
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
})->withoutMiddleware('web');

// URL trigger to run AI classification on unclassified jobs (Schedule::command('app:classify-jobs'))
Route::match(['get', 'post'], '/classify-jobs', fn(\Illuminate\Http\Request $r) => app(CommandRunnerController::class)->execute($r, 'classify-jobs'))->withoutMiddleware('web');
Route::match(['get', 'post'], '/run-classify', fn(\Illuminate\Http\Request $r) => app(CommandRunnerController::class)->execute($r, 'classify-jobs'))->withoutMiddleware('web');
Route::match(['get', 'post'], '/run-classify-jobs', fn(\Illuminate\Http\Request $r) => app(CommandRunnerController::class)->execute($r, 'classify-jobs'))->withoutMiddleware('web');

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

// =========================================================================
// Database Synchronization Routes (Direct GET requests, no parameters needed)
// Simply open these URLs in your browser:
//   - GET /verify-job-count
//   - GET /verify-import-data
//   - GET /reset-production-jobs
//   - GET /import-production-jobs
//   - GET /classify-jobs
// Also supports /command/{command_name} with GET
// =========================================================================
Route::get('/verify-job-count', fn(\Illuminate\Http\Request $r) => app(CommandRunnerController::class)->execute($r, 'verify-job-count'))->withoutMiddleware('web');
Route::get('/verify-import-data', fn(\Illuminate\Http\Request $r) => app(CommandRunnerController::class)->execute($r, 'verify-import-data'))->withoutMiddleware('web');
Route::get('/reset-production-jobs', fn(\Illuminate\Http\Request $r) => app(CommandRunnerController::class)->execute($r, 'reset-production-jobs'))->withoutMiddleware('web');
Route::get('/import-production-jobs', fn(\Illuminate\Http\Request $r) => app(CommandRunnerController::class)->execute($r, 'import-production-jobs'))->withoutMiddleware('web');

Route::match(['get', 'post'], '/command/{command_name}', [CommandRunnerController::class, 'execute'])
    ->withoutMiddleware('web');
