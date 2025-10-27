<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\LessonResource;
use App\Http\Resources\LessonCollection;
use App\Http\Requests\LessonCreateRequest;
use App\Http\Requests\LessonUpdateRequest;
use App\Http\Resources\LessonDetailResource;
use App\Http\Resources\EpubResource;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function create(int $course_id, int $module_id, LessonCreateRequest $request): JsonResponse
    {
        if ($request->user()->can('create courses')) {
            $data = $request->validated();
            $data['module_id'] = $module_id;
            if(Lesson::where('slug', $data['slug'])->exists()) {
                return response()->json(['message' => 'Lesson with this slug already exists'], 422);
            }

            $max_position = Lesson::where('module_id', $module_id)->max('position');
            if ($data['position'] == null) {
                $data['position'] = $max_position + 1;
            }

            if ($data['position'] > $max_position + 1) {
                $data['position'] = $max_position + 1;
            }

            if ($data['position'] < 1) {
                $data['position'] = 1;
            }

            if ($data['position'] < $max_position && $data['position'] >= 1) {
                $lessons = Lesson::where('module_id', $module_id)
                    ->where('position', '>=', $data['position'])
                    ->get();
                foreach ($lessons as $l) {
                    $l->position += 1;
                    $l->save();
                }
            }
            $lesson = Lesson::create($data);
            
            // Clear course caches and related caches
            CacheService::invalidateCourseCache($course_id);
            Cache::forget("module_" . $module_id);
            Cache::forget("lessons_" . $lesson->id);
            Cache::forget("lessons_list_{$module_id}");
            
            return (new LessonResource($lesson))->response()->setStatusCode(201);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function listByModule(int $course_id, int $module_id, Request $request)
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "lessons_list_{$module_id}";
            $lessons = Cache::remember($cacheKey, 3600, function() use ($module_id) {
                Log::info("Fetching lessons from database for module_id: {$module_id}");
                $lessons = Lesson::with('epub') // Eager load epub
                    ->where('module_id', $module_id)
                    ->orderByRaw('COALESCE(position, 0)')
                    ->get();
                Log::info("Lessons fetched from database: ", $lessons->toArray());
                return $lessons;
            });

            if ($lessons->isEmpty()) {
                Log::warning("No lessons found for module_id: {$module_id}");
            } else {
                Log::info("Lessons from cache or database: ", $lessons->toArray());
            }

            return new LessonCollection($lessons);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function get(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "lesson_{$lesson_id}";
            $lesson = Cache::remember($cacheKey, 3600, function() use ($module_id, $lesson_id) {
                Log::info("Fetching lesson from database for lesson_id: {$lesson_id}");
                $lesson = Lesson::with('epub') // Eager load epub
                    ->where('module_id', $module_id)
                    ->where('id', $lesson_id)
                    ->firstOrFail();
                Log::info("Lesson fetched from database with epub: ", $lesson->toArray());
                return $lesson;
            });

            if ($lesson === null) {
            Log::warning("No lesson found for lesson_id: {$lesson_id}");
            } else {
            Log::info("Lesson from cache or database: ", $lesson->toArray());
            }

            $next_lesson = Lesson::where('module_id', $module_id)
            ->where('position', '>', $lesson->position)
            ->orderBy('position')
            ->first();

            $prev_lesson = Lesson::where('module_id', $module_id)
            ->where('position', '<', $lesson->position)
            ->orderBy('position', 'desc')
            ->first();

            Cache::forget("lesson_{$lesson_id}_recent");
            return (new LessonDetailResource($lesson))->additional([
            'next_lesson' => $next_lesson ? new LessonResource($next_lesson) : null,
            'prev_lesson' => $prev_lesson ? new LessonResource($prev_lesson) : null,
            ])->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function getRecent(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "lesson_{$lesson_id}_recent";
            $lesson = Cache::remember($cacheKey, 3600, function() use ($module_id, $lesson_id) {
                Log::info("Fetching lesson from database for lesson_id: {$lesson_id}");
                $lesson = Lesson::where('module_id', $module_id)
                    ->where('id', $lesson_id)
                    ->firstOrFail();
                Log::info("Lesson fetched from database without epub: ", $lesson->toArray());
                return $lesson;
            });

            if ($lesson === null) {
                Log::warning("No lesson found for lesson_id: {$lesson_id}");
            } else {
                Log::info("Lesson from cache or database: ", $lesson->toArray());
            }

            return (new LessonResource($lesson))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function update(int $course_id, int $module_id, int $lesson_id, LessonUpdateRequest $request): JsonResponse
    {
        if ($request->user()->can('edit courses')) {
            $data = $request->validated();
            $lesson = Lesson::where('module_id', $module_id)
                ->where('id', $lesson_id)
                ->firstOrFail();
            if(Lesson::where('slug', $data['slug'])->where('id', '!=', $lesson_id)->exists()) {
                return response()->json(['message' => 'Lesson with this slug already exists'], 422);
            }
            
            $max_position = Lesson::where('module_id', $module_id)->max('position');
            if ($data['position'] !== $lesson->position) {
                if ($data['position'] == null) {
                    $data['position'] = $lesson->position;
                }

                if ($data['position'] > $max_position) {
                    $data['position'] = $max_position;
                }

                if ($data['position'] < 1) {
                    $data['position'] = 1;
                }

                if ($data['position'] < $lesson->position) {
                    $lessons = Lesson::where('module_id', $module_id)
                        ->where('position', '>=', $data['position'])
                        ->where('position', '<', $lesson->position)
                        ->get();
                    foreach ($lessons as $l) {
                        $l->position += 1;
                        $l->save();
                    }
                }

                if ($data['position'] > $lesson->position) {
                    $lessons = Lesson::where('module_id', $module_id)
                        ->where('position', '>', $lesson->position)
                        ->where('position', '<=', $data['position'])
                        ->get();
                    foreach ($lessons as $l) {
                        $l->position -= 1;
                        $l->save();
                    }
                }
            } else {
                $data['position'] = $lesson->position;
            }
    
            $lesson->update($data);
            
            // Clear course caches and related caches
            CacheService::invalidateCourseCache($course_id);
            Cache::forget("module_" . $module_id);
            Cache::forget("lessons_" . $lesson->id);
            Cache::forget("lessons_list_{$module_id}");
            
            return (new LessonResource($lesson))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function delete(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if($request->user()->can('delete courses')) {
            $lesson = Lesson::where('module_id', $module_id)
                ->where('id', $lesson_id)
                ->firstOrFail();

            if($lesson->position !== null) {
                $lessons = Lesson::where('module_id', $module_id)
                    ->where('position', '>', $lesson->position)
                    ->orderBy('position')
                    ->get();
                $current_position = $lesson->position;
                foreach ($lessons as $l) {
                    $l->position = $current_position;
                    $l->save();
                    $current_position++;
                }
            }
            $lesson->delete();
            
            // Clear course caches and related caches
            CacheService::invalidateCourseCache($course_id);
            Cache::forget("module_" . $module_id);
            Cache::forget("lessons_" . $lesson->id);
            Cache::forget("lesson_{$lesson->id}");
            Cache::forget("lessons_list_{$module_id}");
            
            return response()->json(['message' => 'Lesson deleted'], 200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Get lesson with EPUB validation info for file checking
     */
    public function getEpubInfo(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "lesson_epub_info_{$lesson_id}";
            
            $lessonData = Cache::remember($cacheKey, 1800, function() use ($module_id, $lesson_id) {
                $lesson = Lesson::with('epub')
                    ->where('module_id', $module_id)
                    ->where('id', $lesson_id)
                    ->first();

                if (!$lesson) {
                    return null;
                }

                $epubInfo = null;
                if ($lesson->epub) {
                    $epub = $lesson->epub;
                    $filePath = public_path('storage/' . $epub->file_path);
                    
                    $epubInfo = [
                        'id' => $epub->id,
                        'title' => $epub->title,
                        'file_path' => $epub->file_path,
                        'original_filename' => $epub->original_filename ?? basename($epub->file_path),
                        'file_size' => $epub->file_size ?? (file_exists($filePath) ? filesize($filePath) : 0),
                        'mime_type' => $epub->mime_type ?? 'application/epub+zip',
                        'position' => $epub->position ?? 0,
                        'is_active' => $epub->is_active ?? true,
                        'validation' => [
                            'file_exists' => file_exists($filePath),
                            'file_hash' => file_exists($filePath) ? md5_file($filePath) : null,
                            'file_size_bytes' => file_exists($filePath) ? filesize($filePath) : 0,
                            'last_modified' => file_exists($filePath) ? filemtime($filePath) : null,
                            'download_url' => url('storage/' . $epub->file_path),
                            'version_check' => [
                                'db_updated_at' => $epub->updated_at->timestamp,
                                'file_modified_at' => file_exists($filePath) ? filemtime($filePath) : 0,
                                'is_latest' => file_exists($filePath) ? 
                                    ($epub->updated_at->timestamp <= filemtime($filePath)) : false
                            ]
                        ],
                        'created_at' => $epub->created_at,
                        'updated_at' => $epub->updated_at,
                    ];
                }

                return [
                    'lesson' => $lesson,
                    'epub_info' => $epubInfo
                ];
            });

            if ($lessonData === null) {
                return response()->json(['message' => 'Lesson not found'], 404);
            }

            // Get navigation info
            $lesson = $lessonData['lesson'];
            $next_lesson = Lesson::where('module_id', $module_id)
                ->where('position', '>', $lesson->position)
                ->orderBy('position')
                ->first();

            $prev_lesson = Lesson::where('module_id', $module_id)
                ->where('position', '<', $lesson->position)
                ->orderBy('position', 'desc')
                ->first();

            return response()->json([
                'data' => [
                    'lesson' => [
                        'id' => $lesson->id,
                        'module_id' => $lesson->module_id,
                        'title' => $lesson->title,
                        'slug' => $lesson->slug,
                        'cover_image' => $lesson->cover_image,
                        'video_url' => $lesson->video_url,
                        'attachment' => $lesson->attachment,
                        'position' => $lesson->position,
                        'description' => $lesson->description,
                        'created_at' => $lesson->created_at,
                        'updated_at' => $lesson->updated_at,
                    ],
                    'epub_info' => $lessonData['epub_info'],
                    'navigation' => [
                        'next_lesson' => $next_lesson ? [
                            'id' => $next_lesson->id,
                            'title' => $next_lesson->title,
                            'slug' => $next_lesson->slug,
                            'module_id' => $next_lesson->module_id
                        ] : null,
                        'prev_lesson' => $prev_lesson ? [
                            'id' => $prev_lesson->id,
                            'title' => $prev_lesson->title,
                            'slug' => $prev_lesson->slug,
                            'module_id' => $prev_lesson->module_id
                        ] : null
                    ]
                ]
            ], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Check if EPUB file needs to be downloaded/updated
     */
    public function checkEpubVersion(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $lesson = Lesson::with('epub')
                ->where('module_id', $module_id)
                ->where('id', $lesson_id)
                ->first();

            if (!$lesson || !$lesson->epub) {
                return response()->json([
                    'needs_download' => false,
                    'message' => 'No EPUB found for this lesson'
                ], 404);
            }

            $epub = $lesson->epub;
            $filePath = public_path('storage/' . $epub->file_path);
            
            // Check if client has the file info to compare
            $clientFileSize = $request->input('client_file_size', 0);
            $clientFileHash = $request->input('client_file_hash', '');
            $clientLastModified = $request->input('client_last_modified', 0);

            $serverFileExists = file_exists($filePath);
            $serverFileSize = $serverFileExists ? filesize($filePath) : 0;
            $serverFileHash = $serverFileExists ? md5_file($filePath) : '';
            $serverLastModified = $serverFileExists ? filemtime($filePath) : 0;

            $needsDownload = !$serverFileExists || 
                           $clientFileSize !== $serverFileSize ||
                           $clientFileHash !== $serverFileHash ||
                           $clientLastModified < $serverLastModified;

            return response()->json([
                'needs_download' => $needsDownload,
                'server_file_info' => [
                    'exists' => $serverFileExists,
                    'size' => $serverFileSize,
                    'hash' => $serverFileHash,
                    'last_modified' => $serverLastModified,
                    'download_url' => $serverFileExists ? url('storage/' . $epub->file_path) : null
                ],
                'client_file_info' => [
                    'size' => $clientFileSize,
                    'hash' => $clientFileHash,
                    'last_modified' => $clientLastModified
                ],
                'epub_info' => [
                    'id' => $epub->id,
                    'title' => $epub->title,
                    'filename' => $epub->original_filename ?? basename($epub->file_path)
                ]
            ], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Track EPUB reading progress
     */
    public function trackEpubProgress(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'epub_id' => 'required|integer|exists:epubs,id',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'current_page' => 'nullable|integer|min:1',
            'total_pages' => 'nullable|integer|min:1',
            'reading_time_seconds' => 'nullable|integer|min:0',
            'action' => 'nullable|string|in:start,progress,complete,pause,resume',
        ]);

        try {
            $epub = \App\Models\Epub::where('lesson_id', $lesson_id)
                ->where('id', $request->epub_id)
                ->firstOrFail();

            $action = $request->input('action', 'progress');
            
            // Determine if this is completion
            if ($request->progress_percentage >= 100) {
                $action = \App\Models\UserActivity::ACTION_COMPLETE;
            } elseif ($request->progress_percentage > 0) {
                $action = \App\Models\UserActivity::ACTION_PROGRESS;
            }

            // Find existing progress record or create new one
            $existingActivity = \App\Models\UserActivity::where('user_id', $request->user()->id)
                ->where('activity_type', \App\Models\UserActivity::TYPE_EPUB)
                ->where('activity_id', $request->epub_id)
                ->whereIn('action', [\App\Models\UserActivity::ACTION_START, \App\Models\UserActivity::ACTION_PROGRESS])
                ->first();

            $activityData = [
                'user_id' => $request->user()->id,
                'activity_type' => \App\Models\UserActivity::TYPE_EPUB,
                'activity_id' => $request->epub_id,
                'action' => $action,
                'last_seen_url' => $request->fullUrl(),
                'last_seen_at' => now(),
                'progress_percentage' => $request->progress_percentage,
                'metadata' => [
                    'epub_title' => $epub->title,
                    'lesson_id' => $lesson_id,
                    'module_id' => $module_id,
                    'course_id' => $course_id,
                    'current_page' => $request->current_page,
                    'total_pages' => $request->total_pages,
                    'reading_session_seconds' => $request->reading_time_seconds,
                ],
                'device_type' => $this->detectDeviceType($request),
                'user_agent' => $request->header('User-Agent'),
            ];

            if ($action === \App\Models\UserActivity::ACTION_COMPLETE) {
                $activityData['completed_at'] = now();
                
                // Calculate total reading time if we have a start record
                if ($existingActivity && $existingActivity->started_at) {
                    $activityData['duration_seconds'] = now()->diffInSeconds($existingActivity->started_at);
                } elseif ($request->reading_time_seconds) {
                    $activityData['duration_seconds'] = $request->reading_time_seconds;
                }
            }

            // Set started_at if this is the first tracking or start action
            if (!$existingActivity || $action === \App\Models\UserActivity::ACTION_START) {
                $activityData['started_at'] = now();
            } else {
                $activityData['started_at'] = $existingActivity->started_at;
            }

            // Update existing record or create new one
            if ($existingActivity && $action !== \App\Models\UserActivity::ACTION_COMPLETE) {
                $existingActivity->update($activityData);
                $userActivity = $existingActivity;
            } else {
                $userActivity = \App\Models\UserActivity::create($activityData);
            }

            return response()->json([
                'message' => 'EPUB reading progress tracked successfully',
                'data' => [
                    'activity_id' => $userActivity->id,
                    'progress_percentage' => $userActivity->progress_percentage,
                    'action' => $userActivity->action,
                    'reading_time' => $userActivity->duration_seconds,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track EPUB reading progress', [
                'user_id' => $request->user()->id,
                'lesson_id' => $lesson_id,
                'epub_id' => $request->epub_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to track reading progress',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/Tablet/', $userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}