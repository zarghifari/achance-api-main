<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Try to find an admin user first
$adminUser = App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'admin');
})->first();

if ($adminUser) {
    echo 'Found Admin User: ' . $adminUser->name . PHP_EOL;
    $token = $adminUser->createToken('test-token')->plainTextToken;
    echo 'Admin Token: ' . $token . PHP_EOL;
} else {
    // Give permission to the first user
    $user = App\Models\User::first();
    $user->givePermissionTo('create quizzes');
    echo 'Given create quizzes permission to: ' . $user->name . PHP_EOL;
    $token = $user->createToken('test-token')->plainTextToken;
    echo 'User Token: ' . $token . PHP_EOL;
}
