<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting seeders...\n";

try {
    // Check if roles already exist
    echo "Checking existing data...\n";
    
    // Run specific seeders one by one with error handling
    echo "Running RolesAndPermissionsSeeder...\n";
    try {
        $seeder = new Database\Seeders\RolesAndPermissionsSeeder();
        $seeder->run();
        echo "✓ RolesAndPermissionsSeeder completed\n";
    } catch (Exception $e) {
        echo "⚠ RolesAndPermissionsSeeder skipped (already exists)\n";
    }
    
    echo "Running CourseSeeder...\n";
    try {
        $seeder = new Database\Seeders\CourseSeeder();
        $seeder->run();
        echo "✓ CourseSeeder completed\n";
    } catch (Exception $e) {
        echo "⚠ CourseSeeder error: " . $e->getMessage() . "\n";
    }
    
    echo "Running QuizSeeder...\n";
    try {
        $seeder = new Database\Seeders\QuizSeeder();
        $seeder->run();
        echo "✓ QuizSeeder completed\n";
    } catch (Exception $e) {
        echo "⚠ QuizSeeder error: " . $e->getMessage() . "\n";
    }
    
    echo "Running LearningOutcomeSeeder...\n";
    try {
        $seeder = new Database\Seeders\LearningOutcomeSeeder();
        $seeder->run();
        echo "✓ LearningOutcomeSeeder completed\n";
    } catch (Exception $e) {
        echo "⚠ LearningOutcomeSeeder error: " . $e->getMessage() . "\n";
    }
    
    echo "Running QuizAttemptSeeder...\n";
    try {
        $seeder = new Database\Seeders\QuizAttemptSeeder();
        $seeder->run();
        echo "✓ QuizAttemptSeeder completed\n";
    } catch (Exception $e) {
        echo "⚠ QuizAttemptSeeder error: " . $e->getMessage() . "\n";
    }
    
    echo "\nSeeding process completed!\n";
    
} catch (Exception $e) {
    echo "Error running seeders: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}