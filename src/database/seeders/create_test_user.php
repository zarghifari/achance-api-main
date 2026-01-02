<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Create test user
$user = User::where('email', 'test@example.com')->first();

if (!$user) {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    
    // Assign student role to test user
    $studentRole = Role::where('name', 'student')->first();
    if ($studentRole) {
        $user->assignRole($studentRole);
        echo "✅ Test user created successfully with 'student' role!\n";
    } else {
        echo "✅ Test user created successfully (no role assigned - run seeders first)!\n";
    }
    
    echo "Email: {$user->email}\n";
    echo "Password: password123\n";
} else {
    // Check if user has a role, if not assign student role
    if (!$user->hasAnyRole()) {
        $studentRole = Role::where('name', 'student')->first();
        if ($studentRole) {
            $user->assignRole($studentRole);
            echo "✅ Test user already exists - 'student' role assigned!\n";
        } else {
            echo "✅ Test user already exists (no role - run seeders first)!\n";
        }
    } else {
        echo "✅ Test user already exists with roles: " . $user->roles->pluck('name')->join(', ') . "\n";
    }
    
    echo "Email: {$user->email}\n";
    echo "Password: password123\n";
}
