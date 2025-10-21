<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $studentRole = Role::create(['name' => 'student']);
        $teacherRole = Role::create(['name' => 'teacher']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create permissions
        $permissions = [
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',
            'doing own tasks',
            'view all tasks',
            'doing own quizzes',
            'view all attempt quizzes',
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'view own users',
            'view all users',
            'edit users',
            'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $studentRole->givePermissionTo(['view courses', 'doing own tasks', 'doing own quizzes', 'view quizzes', 'view own users', 'edit users']);
        $teacherRole->givePermissionTo(['view courses', 'create courses', 'edit courses', 'delete courses', 'view all tasks', 'view all attempt quizzes', 'view quizzes', 'create quizzes', 'edit quizzes', 'delete quizzes', 'view own users', 'view all users', 'edit users']);
        $adminRole->givePermissionTo(Permission::all());

        // Create users and assign roles
        $student = User::create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'remember_token' => '3|7YGSeLPZvIoYQeV2kU2MCo0lthwENSuP6j9P25yW07f5f882'
        ]);
        $student->assignRole($studentRole);

        $teacher = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'password' => Hash::make('password'),
            'remember_token' => '2|2J2vh3p95lxkFZ0fGFmkfmWBDMGN33NYtNvdxerp3b13a386'
        ]);
        $teacher->assignRole($teacherRole);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'remember_token' => '1|VMAIaxEK0GByli8EybwmoVXqrzfLzgS3Jk304oul1ccab738'
        ]);
        $admin->assignRole($adminRole);
    }
}
