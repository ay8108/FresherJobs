<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class CommandRunnerController extends Controller
{
    /**
     * Strict allowlist of permitted commands.
     */
    private const ALLOWLIST = [
        'verify-job-count' => [
            'artisan_cmd' => 'jobs:verify-data',
            'params' => ['--scope' => 'database'],
            'title' => 'Database Job Count Verification',
        ],
        'verify-import-data' => [
            'artisan_cmd' => 'jobs:verify-data',
            'params' => ['--scope' => 'file'],
            'title' => 'Export Dataset File Verification',
        ],
        'reset-production-jobs' => [
            'artisan_cmd' => 'jobs:reset-production-data',
            'params' => ['--confirm' => 'CONFIRM_RESET_JOBS'],
            'title' => 'Production Job Posts Reset (with Backup)',
        ],
        'import-production-jobs' => [
            'artisan_cmd' => 'jobs:import-data',
            'params' => ['--preserve-ids' => true],
            'title' => 'Production Job Posts Import',
        ],
        'classify-jobs' => [
            'artisan_cmd' => 'app:classify-jobs',
            'params' => ['--limit' => 200, '--batch' => 10],
            'title' => 'AI Job Classification (Gemini)',
        ],
    ];

    /**
     * Execute approved command directly via GET or POST request.
     * No secret token or parameters required.
     */
    public function execute(Request $request, string $command_name)
    {
        if (!array_key_exists($command_name, self::ALLOWLIST)) {
            return response(
                '<div style="font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #f87171; padding: 24px; border-radius: 8px; margin: 24px auto; max-width: 850px;">' .
                '<h2 style="color: #ef4444; margin-top: 0; font-size: 20px;">Command Not Found</h2>' .
                '<p style="color: #e2e8f0;">Unknown command: <code>' . htmlspecialchars($command_name) . '</code></p>' .
                '<p style="color: #94a3b8;">Available synchronization routes:</p>' .
                '<ul style="line-height: 1.8;">' .
                '<li><a href="/verify-job-count" style="color: #38bdf8;">/verify-job-count</a> (Inspect DB count)</li>' .
                '<li><a href="/verify-import-data" style="color: #38bdf8;">/verify-import-data</a> (Verify JSON dataset on disk)</li>' .
                '<li><a href="/reset-production-jobs" style="color: #f87171;">/reset-production-jobs</a> (Backup & clear DB)</li>' .
                '<li><a href="/import-production-jobs" style="color: #4ade80;">/import-production-jobs</a> (Import local jobs)</li>' .
                '<li><a href="/classify-jobs" style="color: #a78bfa;">/classify-jobs</a> (Run AI classification on jobs)</li>' .
                '</ul>' .
                '</div>',
                404
            );
        }

        $cmdDef = self::ALLOWLIST[$command_name];
        set_time_limit(300);
        ignore_user_abort(true);

        $params = $cmdDef['params'];
        if ($request->has('limit')) {
            $limitVal = $request->query('limit');
            if ($limitVal === 'all' || $limitVal === '0') {
                unset($params['--limit']);
            } else {
                $params['--limit'] = (int) $limitVal;
            }
        }
        if ($request->has('batch')) {
            $params['--batch'] = (int) $request->query('batch');
        }

        try {
            $exitCode = Artisan::call($cmdDef['artisan_cmd'], $params);
            $output = Artisan::output();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => $exitCode === 0 ? 'success' : 'error',
                    'command' => $command_name,
                    'title' => $cmdDef['title'],
                    'output' => trim($output),
                ]);
            }

            return response(
                '<div style="font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #38bdf8; padding: 24px; border-radius: 8px; margin: 24px auto; max-width: 850px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">' .
                '<h2 style="color: #4ade80; margin-top: 0; font-size: 20px;">✓ ' . htmlspecialchars($cmdDef['title']) . '</h2>' .
                '<pre style="white-space: pre-wrap; word-break: break-all; color: #e2e8f0; background: #1e293b; padding: 16px; border-radius: 6px; font-size: 13px; line-height: 1.5;">' .
                htmlspecialchars(trim($output) ?: 'Command executed successfully with no output.') .
                '</pre>' .
                '<div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid #334155; font-size: 13px; display: flex; gap: 14px; flex-wrap: wrap;">' .
                '<a href="/verify-job-count" style="color: #60a5fa; text-decoration: underline; font-weight: bold;">1. Verify DB Count</a>' .
                '<span style="color: #475569;">|</span>' .
                '<a href="/verify-import-data" style="color: #60a5fa; text-decoration: underline; font-weight: bold;">2. Verify Dataset File</a>' .
                '<span style="color: #475569;">|</span>' .
                '<a href="/reset-production-jobs" style="color: #f87171; text-decoration: underline; font-weight: bold;">3. Reset DB (Backup)</a>' .
                '<span style="color: #475569;">|</span>' .
                '<a href="/import-production-jobs" style="color: #4ade80; text-decoration: underline; font-weight: bold;">4. Import Dataset</a>' .
                '<span style="color: #475569;">|</span>' .
                '<a href="/classify-jobs" style="color: #a78bfa; text-decoration: underline; font-weight: bold;">5. Classify Jobs (AI)</a>' .
                '<span style="color: #475569;">|</span>' .
                '<a href="/" style="color: #cbd5e1; text-decoration: underline;">Home Page</a>' .
                '</div>' .
                '</div>'
            );
        } catch (Throwable $e) {
            return response(
                '<div style="font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #f87171; padding: 24px; border-radius: 8px; margin: 24px auto; max-width: 850px;">' .
                '<h2 style="color: #ef4444; margin-top: 0; font-size: 20px;">✗ Execution Failed</h2>' .
                '<pre style="white-space: pre-wrap; word-break: break-all; color: #fca5a5; background: #1e293b; padding: 16px; border-radius: 6px; font-size: 13px; line-height: 1.5;">' .
                htmlspecialchars($e->getMessage()) .
                '</pre>' .
                '</div>',
                500
            );
        }
    }
}
