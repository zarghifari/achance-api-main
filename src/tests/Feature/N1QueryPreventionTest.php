<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\User;
use App\Services\QueryOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class N1QueryPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable query logging for tests
        DB::enableQueryLog();
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();
        parent::tearDown();
    }

    /** @test */
    public function course_detail_endpoint_uses_efficient_queries()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $course = Course::factory()->create();
        $modules = Module::factory(3)->create(['course_id' => $course->id]);
        
        foreach ($modules as $module) {
            Lesson::factory(2)->create(['module_id' => $module->id]);
        }

        // Act
        DB::flushQueryLog();
        QueryOptimizationService::startQueryLogging();
        
        $response = $this->getJson("/api/courses/{$course->id}");
        
        $stats = QueryOptimizationService::stopQueryLogging();

        // Assert
        $response->assertOk();
        
        // Should not execute more than 5 queries for course with modules and lessons
        $this->assertLessThanOrEqual(5, $stats['total_queries'], 
            "Too many queries executed: {$stats['total_queries']}. Potential N+1 issue detected."
        );
        
        // Verify the response contains the expected nested data
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'modules' => [
                    '*' => [
                        'id',
                        'title',
                        'lessons' => [
                            '*' => [
                                'id',
                                'title'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function user_activity_endpoint_uses_efficient_queries()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory(3)->create(['module_id' => $module->id]);
        
        // Create user activities
        foreach ($lessons as $lesson) {
            \App\Models\UserActivity::create([
                'user_id' => $user->id,
                'activity_type' => 'lesson',
                'activity_id' => $lesson->id,
                'last_seen_at' => now()
            ]);
        }

        // Act
        DB::flushQueryLog();
        QueryOptimizationService::startQueryLogging();
        
        $response = $this->getJson('/api/user-activities');
        
        $stats = QueryOptimizationService::stopQueryLogging();

        // Assert
        $response->assertOk();
        
        // Should not execute more than 4 queries (activities + lessons + modules + courses)
        $this->assertLessThanOrEqual(4, $stats['total_queries'], 
            "Too many queries executed: {$stats['total_queries']}. Potential N+1 issue in user activities."
        );
    }

    /** @test */
    public function module_list_endpoint_uses_efficient_queries()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $course = Course::factory()->create();
        $modules = Module::factory(5)->create(['course_id' => $course->id]);
        
        foreach ($modules as $module) {
            Lesson::factory(3)->create(['module_id' => $module->id]);
        }

        // Act
        DB::flushQueryLog();
        QueryOptimizationService::startQueryLogging();
        
        $response = $this->getJson("/api/courses/{$course->id}/modules");
        
        $stats = QueryOptimizationService::stopQueryLogging();

        // Assert
        $response->assertOk();
        
        // Should execute at most 3 queries (modules + lessons + epub)
        $this->assertLessThanOrEqual(3, $stats['total_queries'], 
            "Too many queries executed: {$stats['total_queries']}. Potential N+1 issue in module listing."
        );
    }

    /** @test */
    public function query_optimization_service_tracks_statistics()
    {
        // Act
        QueryOptimizationService::startQueryLogging();
        
        // Execute some queries
        User::all();
        Course::with('modules')->get();
        
        $stats = QueryOptimizationService::stopQueryLogging();

        // Assert
        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('total_time', $stats);
        $this->assertArrayHasKey('average_time', $stats);
        $this->assertArrayHasKey('slow_queries', $stats);
        
        $this->assertGreaterThan(0, $stats['total_queries']);
    }

    /** @test */
    public function n1_detection_middleware_adds_headers_in_debug_mode()
    {
        // Arrange
        config(['app.debug' => true]);
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $course = Course::factory()->create();

        // Act
        $response = $this->withMiddleware(['detect.n1'])
                        ->getJson("/api/courses/{$course->id}");

        // Assert
        $response->assertOk();
        $response->assertHeader('X-Query-Count');
        $response->assertHeader('X-Query-Time');
    }
}
