<?php

declare(strict_types=1);

/**
 * One-command project installer for a fresh miPress website.
 *
 * Usage:
 *   php scripts/install-web.php
 *   php scripts/install-web.php --skip-build
 *   php scripts/install-web.php --skip-migrate
 *   php scripts/install-web.php --skip-seed
 *   php scripts/install-web.php --skip-smoke
 */
$arguments = array_slice($argv, 1);

if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
    echo "miPress installer\n";
    echo "Usage: php scripts/install-web.php [--skip-build] [--skip-migrate] [--skip-seed] [--skip-smoke]\n";
    exit(0);
}

$skipBuild = in_array('--skip-build', $arguments, true);
$skipMigrate = in_array('--skip-migrate', $arguments, true);
$skipSeed = in_array('--skip-seed', $arguments, true);
$skipSmoke = in_array('--skip-smoke', $arguments, true);

$run = static function (string $command): void {
    echo "\n> {$command}\n";
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "Command failed (exit {$exitCode}): {$command}\n");
        exit($exitCode);
    }
};

echo "Starting miPress installer...\n";

$run('composer install --no-interaction --prefer-dist --no-progress');

$freshEnv = false;

if (! file_exists('.env')) {
    if (! copy('.env.example', '.env')) {
        fwrite(STDERR, "Failed to create .env from .env.example\n");
        exit(1);
    }

    $freshEnv = true;
    echo "Created .env from .env.example\n";
} else {
    echo ".env already exists, keeping current values\n";
}

if ($freshEnv) {
    $run(PHP_BINARY.' artisan key:generate --no-interaction');
} else {
    echo "APP_KEY already configured, skipping key:generate\n";
}

if (! $skipBuild) {
    $run('npm ci');
    $run('npm run build');
} else {
    echo "Skipping frontend build (--skip-build)\n";
}

if (! $skipMigrate) {
    $run(PHP_BINARY.' artisan migrate --force --no-interaction');
} else {
    echo "Skipping migrations (--skip-migrate)\n";
}

if (! $skipSeed) {
    $run(PHP_BINARY.' artisan db:seed --force --no-interaction');
} else {
    echo "Skipping database seeder (--skip-seed)\n";
}

$run(PHP_BINARY.' artisan storage:link --no-interaction --force');
$run(PHP_BINARY.' artisan optimize:clear');

if (! $skipSmoke) {
    $run('composer test:smoke');
} else {
    echo "Skipping smoke tests (--skip-smoke)\n";
}

echo "\nmiPress installation completed successfully.\n";
