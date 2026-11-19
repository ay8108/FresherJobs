<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CommandRunnerController extends Controller
{
    /**
     * Strict allowlist of permitted commands.
     * Maps public URL command slug to internal Artisan signature and safe fixed parameters.
     */
    private const ALLOWLIST = [
        'verify-import-data' => [
            'artisan_cmd' => 'jobs:verify-data',
            'params' => ['--scope' => 'file'],
            'destructive' => false,
            'description' => 'Verify exported dataset file integrity, size, and record count',
        ],
        'verify-job-count' => [
            'artisan_cmd' => 'jobs:verify-data',
            'params' => ['--scope' => 'database'],
            'destructive' => false,
            'description' => 'Verify current database job count and source distribution',
        ],
        'reset-production-jobs' => [
            'artisan_cmd' => 'jobs:reset-production-data',
            'params' => ['--confirm' => 'CONFIRM_RESET_JOBS'],
            'destructive' => true,
            'description' => 'Back up existing production jobs and truncate job_posts table',
        ],
        'import-production-jobs' => [
            'artisan_cmd' => 'jobs:import-data',
            'params' => ['--preserve-ids' => true],
            'destructive' => false,
            'description' => 'Import exported dataset records into job_posts table',
        ],
    ];

    /**
     * Handle POST request to execute an approved Artisan command.
     */
    public function handle(Request $request, string $command_name): JsonResponse
    {
        // 1. Check if the command runner route is globally enabled
        $routeEnabled = filter_var(
            env('COMMAND_ROUTE_ENABLED', true),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? true;

        if (!$routeEnabled) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Command route is disabled on this server (COMMAND_ROUTE_ENABLED=false).',
            ], 403);
        }

        // 2. Validate production secret token configuration
        $configuredSecret = env('COMMAND_SECRET_KEY') ?: env('SYNC_SECRET_TOKEN');

        if (empty($configuredSecret)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'COMMAND_SECRET_KEY is not configured in server environment. Remote command execution is disabled.',
            ], 503);
        }

        // 3. Authenticate incoming request token
        $providedToken = $request->header('X-Command-Secret')
            ?: ($request->bearerToken() ?: $request->input('secret'));

        if (empty($providedToken) || !hash_equals((string) $configuredSecret, (string) $providedToken)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid or missing secret token.',
            ], 401);
        }

        // 4. Validate requested command against strict allowlist
        if (!array_key_exists($command_name, self::ALLOWLIST)) {
            return response()->json([
                'status'               => 'error',
                'message'              => "Command '{$command_name}' is not recognized or not allowlisted.",
                'allowlisted_commands' => array_keys(self::ALLOWLIST),
            ], 404);
        }

        $cmdDef = self::ALLOWLIST[$command_name];

        // 5. Concurrency protection via atomic cache lock
        $lockKey = 'cmd_runner_lock_' . $command_name;
        $lock = Cache::lock($lockKey, 300); // 5-minute timeout lock

        if (!$lock->get()) {
            return response()->json([
                'status'  => 'error',
                'message' => "Another execution of '{$command_name}' is currently in progress. Please wait.",
            ], 429);
        }

        // 6. Anti-replay protection for destructive operations
        if ($cmdDef['destructive']) {
            $lastRun = Cache::get('last_run_' . $command_name);
            if ($lastRun && (time() - $lastRun < 60)) {
                $lock->release();
                return response()->json([
                    'status'  => 'error',
                    'message' => "Destructive command was executed within the last 60 seconds. Please wait before retrying.",
                ], 429);
            }
        }

        // 7. Execute allowlisted command safely
        set_time_limit(300);
        ignore_user_abort(true);

        $startTime = microtime(true);

        try {
            $exitCode = Artisan::call($cmdDef['artisan_cmd'], $cmdDef['params']);
            $rawOutput = Artisan::output();
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($cmdDef['destructive']) {
                Cache::put('last_run_' . $command_name, time(), 120);
            }

            // Sanitize output - strip any accidental secret tokens
            $sanitizedOutput = str_replace($configuredSecret, '***REDACTED***', $rawOutput);

            return response()->json([
                'status'          => $exitCode === 0 ? 'success' : 'error',
                'exit_code'       => $exitCode,
                'command'         => $command_name,
                'artisan_command' => $cmdDef['artisan_cmd'],
                'description'     => $cmdDef['description'],
                'duration_ms'     => $durationMs,
                'executed_at'     => now()->toIso8601String(),
                'output'          => trim($sanitizedOutput),
            ], $exitCode === 0 ? 200 : 500);

        } catch (Throwable $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'status'      => 'error',
                'command'     => $command_name,
                'message'     => 'Command execution failed: ' . $e->getMessage(),
                'duration_ms' => $durationMs,
            ], 500);

        } finally {
            $lock->release();
        }
    }

    /**
     * Reject GET requests to /command/{command_name} with descriptive instructions.
     */
    public function rejectGet(string $command_name): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Method Not Allowed. The /command/{command_name} endpoint accepts POST requests only.',
            'instructions' => [
                'method'  => 'POST',
                'url'     => url("/command/{$command_name}"),
                'headers' => [
                    'X-Command-Secret' => '<YOUR_SECRET_TOKEN>',
                    'Content-Type'     => 'application/json',
                ],
                'allowlisted_commands' => array_keys(self::ALLOWLIST),
            ],
        ], 405);
    }
}
