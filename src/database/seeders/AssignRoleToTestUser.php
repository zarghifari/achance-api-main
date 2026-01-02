<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignRoleToTestUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find test user
        $user = User::where('email', 'test@example.com')->first();
        
        if (!$user) {
            $this->command->error('❌ Test user not found. Please create user first.');
            return;
        }
        
        // Check if user already has roles
        if ($user->hasAnyRole()) {
            $roles = $user->roles->pluck('name')->join(', ');
            $this->command->info("✅ Test user already has roles: {$roles}");
            return;
        }
        
        // Assign student role
        $studentRole = Role::where('name', 'student')->first();
        
        if (!$studentRole) {
            $this->command->error('❌ Student role not found. Please run RolesAndPermissionsSeeder first.');
            return;
        }
        
        $user->assignRole($studentRole);
        
        $this->command->info('✅ Successfully assigned "student" role to test@example.com');
        $this->command->info("   Email: {$user->email}");
        $this->command->info('   Password: password123');
        $this->command->info('   Role: student');
    }
}
