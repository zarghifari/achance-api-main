<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
echo 'User: ' . $user->name . PHP_EOL;
echo 'Permissions: ' . PHP_EOL;
foreach ($user->getAllPermissions() as $permission) {
    echo '  - ' . $permission->name . PHP_EOL;
}
echo 'Can create quizzes: ' . ($user->can('create quizzes') ? 'YES' : 'NO') . PHP_EOL;
