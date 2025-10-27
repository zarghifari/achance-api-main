<?php
namespace App\Http\Controllers;

use App\Http\Requests\EpubCreateRequest;
use App\Http\Requests\EpubUpdateRequest;
use App\Http\Resources\EpubResource;
use App\Models\Epub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EpubController extends Controller
{
    /**
     * Store a newly created epub.
     */
    public function create(int $course_id, int $module_id, int $lesson_id, EpubCreateRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $data = $request->validated();
            $data['lesson_id'] = $lesson_id;
            
            // Handle file upload if present
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                
                // Generate safe filename
                $timestamp = time();
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $safeName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $baseName);
                $fileName = $timestamp . '_' . $safeName . '.epub';
                
                // Store in public disk so it's accessible
                $filePath = $file->storeAs('uploads/epubs', $fileName, 'public');
                $data['file_path'] = $filePath;
                $data['original_filename'] = $originalName;
                $data['file_size'] = $file->getSize();
                $data['mime_type'] = $file->getMimeType() ?: 'application/epub+zip';
                
                Log::info('File uploaded successfully', [
                    'original_name' => $originalName,
                    'stored_path' => $filePath,
                    'file_size' => $file->getSize()
                ]);
            } 
            // Handle existing file path (for testing with seeded files)
            elseif ($request->filled('file_path')) {
                $filePath = $request->input('file_path');
                $fullPath = public_path('storage/' . $filePath);
                
                if (file_exists($fullPath)) {
                    $data['file_path'] = $filePath;
                    $data['original_filename'] = basename($filePath);
                    $data['file_size'] = filesize($fullPath);
                    $data['mime_type'] = 'application/epub+zip';
                    
                    Log::info('Using existing file', [
                        'file_path' => $filePath,
                        'size' => $data['file_size']
                    ]);
                } else {
                    Log::warning('Existing file not found', ['path' => $fullPath]);
                }
            }
            // No file provided - create EPUB record without file
            else {
                Log::info('Creating EPUB without file', [
                    'title' => $data['title'] ?? 'Untitled EPUB',
                    'lesson_id' => $lesson_id
                ]);
            }

            // Set defaults
            $data['is_active'] = $data['is_active'] ?? true;
            $data['position'] = $data['position'] ?? 0;

            $epub = Epub::create($data);

            // Cache management
            Cache::forget("lesson_{$lesson_id}");
            Cache::put("epub_{$epub->id}", $epub, 3600);
            Cache::forget("epubs_list_{$lesson_id}");

            return (new EpubResource($epub))->response()->setStatusCode(201);
            
        } catch (\Exception $e) {
            Log::error('EPUB creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Failed to create EPUB',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get epub by lesson ID.
     */
    public function get(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $epub = Cache::remember("epub_{$lesson_id}", 3600, function () use ($lesson_id) {
            return Epub::where('lesson_id', $lesson_id)->firstOrFail();
        });

        // Track EPUB view activity
        try {
            \App\Models\UserActivity::create([
                'user_id' => $request->user()->id,
                'activity_type' => \App\Models\UserActivity::TYPE_EPUB,
                'activity_id' => $epub->id,
                'action' => \App\Models\UserActivity::ACTION_VIEW,
                'last_seen_url' => $request->fullUrl(),
                'last_seen_at' => now(),
                'metadata' => [
                    'epub_title' => $epub->title,
                    'lesson_id' => $lesson_id,
                    'module_id' => $module_id,
                    'course_id' => $course_id,
                ],
                'device_type' => $this->detectDeviceType($request),
                'user_agent' => $request->header('User-Agent'),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track EPUB view activity', [
                'epub_id' => $epub->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
        }

        return (new EpubResource($epub))->response()->setStatusCode(200);
    }

    /**
     * Download epub file.
     */
    public function download(int $course_id, int $module_id, int $lesson_id, int $epub_id, Request $request)
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $epub = Epub::where('lesson_id', $lesson_id)
                    ->where('id', $epub_id)
                    ->firstOrFail();

        if (!$epub->file_path) {
            return response()->json(['message' => 'No file associated with this EPUB'], 404);
        }

        $filePath = public_path('storage/' . $epub->file_path);
        
        if (!file_exists($filePath)) {
            Log::error('EPUB file not found', [
                'epub_id' => $epub_id,
                'file_path' => $epub->file_path,
                'full_path' => $filePath
            ]);
            return response()->json(['message' => 'File not found'], 404);
        }

        // Track EPUB download activity
        try {
            \App\Models\UserActivity::create([
                'user_id' => $request->user()->id,
                'activity_type' => \App\Models\UserActivity::TYPE_EPUB,
                'activity_id' => $epub_id,
                'action' => \App\Models\UserActivity::ACTION_DOWNLOAD,
                'last_seen_url' => $request->fullUrl(),
                'last_seen_at' => now(),
                'started_at' => now(),
                'metadata' => [
                    'epub_title' => $epub->title,
                    'file_size' => $epub->file_size,
                    'original_filename' => $epub->original_filename,
                    'lesson_id' => $lesson_id,
                    'module_id' => $module_id,
                    'course_id' => $course_id,
                ],
                'device_type' => $this->detectDeviceType($request),
                'user_agent' => $request->header('User-Agent'),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track EPUB download activity', [
                'epub_id' => $epub_id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
        }

        $fileName = $epub->original_filename ?: basename($epub->file_path);
        
        return response()->download($filePath, $fileName, [
            'Content-Type' => $epub->mime_type ?: 'application/epub+zip',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Update an existing epub.
     */
    public function update(int $course_id, int $module_id, int $lesson_id, int $epub_id, EpubUpdateRequest $request): JsonResponse
    {
        if ($request->user()->cannot('edit courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        $epub = Epub::where('lesson_id', $lesson_id)
                    ->where('id', $epub_id)
                    ->firstOrFail();

        // Handle file upload if present
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\-\.]/', '', $originalName);
            $filePath = $file->storeAs('uploads/epubs', $fileName, 'public');
            $data['file_path'] = $filePath;
        }

        $epub->update($data);

        // Refresh the model to get updated data
        $epub->refresh();

        Cache::forget("lesson_{$lesson_id}");
        Cache::forget("epubs_list_{$lesson_id}");
        Cache::forget("epub_{$epub_id}");

        return (new EpubResource($epub))->response()->setStatusCode(200);
    }

    /**
     * Delete an existing epub.
     */
    public function delete(int $course_id, int $module_id, int $lesson_id, int $epub_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('delete courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $epub = Epub::where('lesson_id', $lesson_id)
                    ->where('id', $epub_id)
                    ->firstOrFail();

        // Delete the physical file if it exists
        if ($epub->file_path && Storage::disk('public')->exists($epub->file_path)) {
            Storage::disk('public')->delete($epub->file_path);
            Log::info('File deleted successfully', [
                'file_path' => $epub->file_path,
                'epub_id' => $epub_id
            ]);
        }

        // Delete the database record
        $epub->delete();

        // Clear related caches
        Cache::forget("lesson_{$lesson_id}");
        Cache::forget("epubs_list_{$lesson_id}");
        Cache::forget("epub_{$epub_id}");

        return response()->json(['message' => 'Epub deleted successfully'], 200);
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

