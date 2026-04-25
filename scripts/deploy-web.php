<?php

declare(strict_types=1);

/**
 * One-command deployment script for a personal miPress project.
 *
 * Usage:
 *   php scripts/deploy-web.php
 *   php scripts/deploy-web.php --skip-build
 *   php scripts/deploy-web.php --skip-maintenance
 */
$arguments = array_slice($argv, 1);

if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
    echo "miPress deploy script\n";
    echo "Usage: php scripts/deploy-web.php [--skip-build] [--skip-maintenance]\n";
    exit(0);
}

$skipBuild = in_array('--skip-build', $arguments, true);
$skipMaintenance = in_array('--skip-maintenance', $arguments, true);

$run = static function (string $command, bool $allowFailure = false): int {
    echo "\n> {$command}\n";
    passthru($command, $exitCode);

    if ($exitCode !== 0 && ! $allowFailure) {
        throw new RuntimeException("Command failed (exit {$exitCode}): {$command}", $exitCode);
    }

    return $exitCode;
};

$runAndCapture = static function (string $command): string {
    echo "\n> {$command}\n";

    $outputLines = [];
    exec($command.' 2>&1', $outputLines, $exitCode);

    $output = implode(PHP_EOL, $outputLines);

    if ($output !== '') {
        echo $output.PHP_EOL;
    }

    if ($exitCode !== 0) {
        throw new RuntimeException("Command failed (exit {$exitCode}): {$command}", $exitCode);
    }

    return $output;
};

$runOptional = static function (string $command) use ($run): void {
    $exitCode = $run($command, true);

    if ($exitCode !== 0) {
        echo "Non-blocking command failed, continuing deployment\n";
    }
};

$commandExists = static function (string $command): bool {
    $probe = DIRECTORY_SEPARATOR === '\\'
        ? "where {$command} >NUL 2>NUL"
        : "command -v {$command} >/dev/null 2>&1";

    exec($probe, $output, $exitCode);

    return $exitCode === 0;
};

echo "Starting miPress deployment...\n";

$maintenanceModeEnabled = false;

try {
    $run('composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist');

    if (! $skipBuild) {
        if ($commandExists('npm')) {
            $run('npm ci');
            $run('npm run build');
        } else {
            echo "npm not found, skipping frontend build\n";
        }
    } else {
        echo "Skipping frontend build (--skip-build)\n";
    }

    $pretendOutput = $runAndCapture(PHP_BINARY.' artisan migrate --pretend --no-interaction');

    if (preg_match('/drop\s+table|truncate\s+table|drop\s+column/i', $pretendOutput) === 1) {
        throw new RuntimeException('Potentially destructive migration detected, aborting deployment.');
    }

    if (! $skipMaintenance) {
        $maintenanceModeEnabled = $run(PHP_BINARY.' artisan down --render="errors::503" --retry=60', true) === 0;

        if (! $maintenanceModeEnabled) {
            echo "Unable to enable maintenance mode, continuing deployment\n";
        }
    } else {
        echo "Skipping maintenance mode (--skip-maintenance)\n";
    }

    $run(PHP_BINARY.' artisan migrate --force --no-interaction');
    $runOptional(PHP_BINARY.' artisan storage:link --no-interaction --force');
    $run(PHP_BINARY.' artisan mipress:publish-assets --no-interaction');
    $run(PHP_BINARY.' artisan optimize:clear');
    $run(PHP_BINARY.' artisan config:cache');
    $run(PHP_BINARY.' artisan route:cache');
    $run(PHP_BINARY.' artisan view:cache');
    $run(PHP_BINARY.' artisan filament:cache-components');
    $runOptional(PHP_BINARY.' artisan queue:restart');

    if ($maintenanceModeEnabled) {
        $run(PHP_BINARY.' artisan up');
    }

    echo "\nmiPress deployment completed successfully.\n";
} catch (Throwable $throwable) {
    if ($maintenanceModeEnabled) {
        $run(PHP_BINARY.' artisan up', true);
    }

    fwrite(STDERR, $throwable->getMessage().PHP_EOL);
    exit($throwable->getCode() !== 0 ? $throwable->getCode() : 1);
}
