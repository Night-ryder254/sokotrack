<?php

// Fails the Docker build if a class the running app needs is missing from the production install.
//
// `composer install --no-dev` leaves out everything in "require-dev". Anything the app uses at
// runtime, not only in tests, must therefore be in "require". Run from the project root, after
// composer install.

$autoload = getcwd() . '/vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "vendor/autoload.php not found in " . getcwd() . "\n");
    exit(2);
}

require $autoload;

$needed = [
    'Faker\\Factory' => 'fakerphp/faker: used by fake() in CheckoutController (bank transfer references) and by the database seeders',
];

$missing = [];

foreach ($needed as $class => $why) {
    if (! class_exists($class)) {
        $missing[] = "  - {$class}  ({$why})";
    }
}

if ($missing) {
    fwrite(STDERR, "Missing from the production install (composer install --no-dev):\n" . implode("\n", $missing) . "\n");
    fwrite(STDERR, "Move the package from require-dev to require, for example:  composer require fakerphp/faker\n");
    exit(1);
}

echo "Runtime dependencies are present.\n";
