<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Achance API Documentation",
 *     description="Comprehensive API documentation for the Achance Learning Management System",
 *     termsOfService="http://localhost/terms/",
 *     @OA\Contact(
 *         name="API Support",
 *         email="support@achance.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Main API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Laravel Sanctum token authentication. Use 'Bearer {your-token}'"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Courses",
 *     description="Course management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Modules",
 *     description="Module management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Lessons",
 *     description="Lesson management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Quizzes",
 *     description="Quiz management and attempt endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Tasks",
 *     description="Task management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 */
abstract class BaseController extends Controller
{
    //
}