<?php

declare(strict_types=1);

/**
 * One-command project installer for a fresh miPress website.
 *
 * Usage:
 *   php scripts/install-web.php
 *   php scripts/install-web.php --skip-build
 *   php scripts/install-web.php --skip-migrate
 *   php scripts/install-web.php --skip-smoke
 */

$arguments = array_slice($argv, 1);

if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
    echo "miPress installer\n";
    echo "Usage: php scripts/install-web.php [--skip-build] [--skip-migrate] [--skip-smoke]\n";
    exit(0);
}

$skipBuild = in_array('--skip-build', $arguments, true);
$skipMigrate = in_array('--skip-migrate', $arguments, true);
$skipSmoke = in_array('--skip-smoke', $arguments, true);

$run = static function (string $command): void {
    echo "\n> {$command}\n";
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "Command failed (exit {$exitCode}): {$command}\n");
        exit($exitCode);
    }
};

$commandOutput = static function (string $command): string {
    echo "\n> {$command}\n";
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    $text = implode(PHP_EOL, $output);

    if ($text !== '') {
        echo $text . PHP_EOL;
    }

    if ($exitCode !== 0) {
        fwrite(STDERR, "Command failed (exit {$exitCode}): {$command}\n");
        exit($exitCode);
    }

    return $text;
};

echo "Starting miPress installer...\n";

$run('composer install --no-interaction --prefer-dist --no-progress');

if (! file_exists('.env')) {
    if (! copy('.env.example', '.env')) {
        fwrite(STDERR, "Failed to create .env from .env.example\n");
        exit(1);
    }

    echo "Created .env from .env.example\n";
} else {
    echo ".env already exists, keeping current values\n";
}

$run(PHP_BINARY . ' artisan key:generate --no-interaction --force');

if (! $skipBuild) {
    $run('npm ci');
    $run('npm run build');
} else {
    echo "Skipping frontend build (--skip-build)\n";
}

if (! $skipMigrate) {
    $pretendOutput = $commandOutput(PHP_BINARY . ' artisan migrate --pretend --no-interaction');

    if (preg_match('/\b(drop\s+table|truncate\s+table|drop\s+column)\b/i', $pretendOutput) === 1) {
        fwrite(STDERR, "Potentially destructive migration detected in --pretend output. Aborting.\n");
        exit(1);
    }

    $run(PHP_BINARY . ' artisan migrate --force --no-interaction');
} else {
    echo "Skipping migrations (--skip-migrate)\n";
}

$run(PHP_BINARY . ' artisan optimize:clear');

if (! $skipSmoke) {
    $run('composer test:smoke');
} else {
    echo "Skipping smoke tests (--skip-smoke)\n";
}

echo "\nmiPress installation completed successfully.\n";
