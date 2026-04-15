<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Show provider registration order
$providers = array_keys(app()->getLoadedProviders());
$relevant = array_filter($providers, function ($p) {
    return str_contains($p, 'MiPress') || str_contains($p, 'AdminPanel') || str_contains($p, 'SocialFeeds') || str_contains($p, 'FormsService') || str_contains($p, 'Filament');
});
echo 'Provider order:'.PHP_EOL;
foreach ($relevant as $i => $p) {
    echo "  [{$i}] {$p}".PHP_EOL;
}

// Now check translation state
$t = app('translator');
$ref = new ReflectionProperty($t, 'loaded');
$loaded = $ref->getValue($t);
echo PHP_EOL.'Loaded translation state:'.PHP_EOL;
foreach ($loaded as $ns => $groups) {
    foreach ($groups as $group => $locales) {
        foreach ($locales as $locale => $data) {
            $count = is_array($data) ? count($data) : 0;
            echo "  {$ns}::{$group} [{$locale}] => {$count} top-level keys".PHP_EOL;
        }
    }
}
