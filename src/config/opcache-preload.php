<?php

// OPcache Preload Script for Laravel
// This file should be preloaded to improve performance

// Check if OPcache is available before attempting to use it
if (!function_exists('opcache_compile_file')) {
    return;
}

// Only preload vendor files to avoid class redeclaration issues
$vendorFiles = [
    __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Foundation/helpers.php',
    __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Support/helpers.php',
];

foreach ($vendorFiles as $file) {
    if (file_exists($file)) {
        opcache_compile_file($file);
    }
}
