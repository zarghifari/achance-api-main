<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$token = $user->createToken('test-token')->plainTextToken;
echo 'User: ' . $user->name . PHP_EOL;
echo 'Token: ' . $token . PHP_EOL;
