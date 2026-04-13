<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('app:health-check')]
#[Description('Provede diagnostiku klíčových služeb (DB, cache, queue, storage)')]
class HealthCheckCommand extends Command
{
    public function handle(): int
    {
        $failed = false;

        // Database
        $this->checkService('Database', function () {
            DB::select('SELECT 1');
        }, $failed);

        // Cache
        $this->checkService('Cache', function () {
            $key = 'health-check:'.now()->timestamp;
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== true) {
                throw new \RuntimeException('Cache read/write failed');
            }
        }, $failed);

        // Queue (check table exists and is accessible)
        $this->checkService('Queue (jobs table)', function () {
            DB::table('jobs')->count();
        }, $failed);

        // Failed jobs count
        $failedJobsCount = 0;
        $this->checkService('Failed jobs', function () use (&$failedJobsCount) {
            $failedJobsCount = DB::table('failed_jobs')->count();

            if ($failedJobsCount > 0) {
                throw new \RuntimeException("{$failedJobsCount} failed job(s) in queue");
            }
        }, $failed);

        // Storage writable
        $this->checkService('Storage', function () {
            $path = storage_path('framework/health-check.tmp');
            file_put_contents($path, 'ok');
            $content = file_get_contents($path);
            unlink($path);

            if ($content !== 'ok') {
                throw new \RuntimeException('Storage read/write failed');
            }
        }, $failed);

        // Scheduler (check last scheduled run via cache marker)
        $lastRun = Cache::get('scheduler:last-run');
        if ($lastRun) {
            $this->components->info("Scheduler last run: {$lastRun}");
        } else {
            $this->components->warn('Scheduler: no recent run detected (marker not set)');
        }

        $this->newLine();

        if ($failed) {
            $this->components->error('Health check completed with failures.');

            return self::FAILURE;
        }

        $this->components->info('All health checks passed.');

        return self::SUCCESS;
    }

    private function checkService(string $name, callable $check, bool &$failed): void
    {
        try {
            $check();
            $this->components->twoColumnDetail($name, '<fg=green;options=bold>OK</>');
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail($name, '<fg=red;options=bold>FAIL</>');
            $this->components->error("  {$e->getMessage()}");
            $failed = true;
        }
    }
}
