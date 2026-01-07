<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContentResource;
use App\Models\Content;
use App\Models\Lesson;
use App\Services\DocumentConverterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    protected $documentConverter;

    public function __construct(DocumentConverterService $documentConverter)
    {
        $this->documentConverter = $documentConverter;
    }

    /**
     * Import and convert document (.doc, .docx, .html) to HTML/JSON with attachments
     */
    public function import(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('create courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:doc,docx,html,htm|max:51200', // 50MB max
            'words_per_page' => 'nullable|integer|min:100|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $sourceFileSize = $file->getSize();
            
            // Store original file
            $sourcePath = $file->store('uploads/contents/source', 'public');
            $fullSourcePath = storage_path('app/public/' . $sourcePath);

            Log::info('Starting document import', [
                'filename' => $originalFilename,
                'size' => $sourceFileSize,
                'lesson_id' => $lesson_id,
                'source_path' => $fullSourcePath
            ]);

            // Set pagination preferences
            if ($request->filled('words_per_page')) {
                $this->documentConverter->setWordsPerPage($request->input('words_per_page'));
            }

            // Convert document to HTML/JSON
            $converted = $this->documentConverter->convertDocument($fullSourcePath, $originalFilename);

            // Auto-generate title from filename if not provided
            $title = $request->input('title');
            if (!$title) {
                $title = pathinfo($originalFilename, PATHINFO_FILENAME);
                $title = ucwords(str_replace(['-', '_'], ' ', $title));
            }

            // Create content record
            $content = Content::create([
                'lesson_id' => $lesson_id,
                'title' => $title,
                'description' => $request->input('description'),
                'type' => 'html',
                'original_filename' => $originalFilename,
                'source_file_path' => $sourcePath,
                'source_file_size' => $sourceFileSize,
                'html_content' => $converted['html_content'],
                'json_content' => $converted['json_content'],
                'images' => $converted['images'],
                'assets' => $converted['assets'],
                'total_pages' => $converted['total_pages'],
                'words_per_page' => $request->input('words_per_page', 500),
                'metadata' => $converted['metadata'],
                'is_processed' => true,
                'processed_at' => now(),
                'is_active' => true,
                'position' => 0
            ]);

            // Clear lesson cache
            Cache::forget("lesson_{$lesson_id}");

            Log::info('Document imported successfully', [
                'content_id' => $content->id,
                'pages' => $content->total_pages,
                'images' => count($content->images ?? [])
            ]);

            return response()->json([
                'message' => 'Document imported and processed successfully',
                'data' => new ContentResource($content),
                'processing_info' => [
                    'pages_created' => $content->total_pages,
                    'images_processed' => count($content->images ?? []),
                    'word_count' => $converted['metadata']['word_count'] ?? 0
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Document import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to import document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content for a lesson
     */
    public function get(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $content = Cache::remember("content_{$lesson_id}", 3600, function () use ($lesson_id) {
            return Content::where('lesson_id', $lesson_id)
                ->where('is_active', true)
                ->firstOrFail();
        });

        // Track content view activity
        $this->trackActivity($request, $content, 'view');

        return (new ContentResource($content))->response()->setStatusCode(200);
    }

    /**
     * Get paginated content (specific page)
     */
    public function getPage(int $course_id, int $module_id, int $lesson_id, int $page, Request $request): JsonResponse
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cacheKey = "content_{$lesson_id}_page_{$page}";
        
        $pageData = Cache::remember($cacheKey, 3600, function () use ($lesson_id, $page) {
            $content = Content::where('lesson_id', $lesson_id)
                ->where('is_active', true)
                ->firstOrFail();
            
            return $content->getPageContent($page);
        });

        if (!$pageData) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Track page view
        $this->trackActivity($request, null, 'page_view', ['page' => $page, 'lesson_id' => $lesson_id]);

        return response()->json([
            'data' => $pageData
        ]);
    }

    /**
     * Update content
     */
    public function update(int $course_id, int $module_id, int $lesson_id, int $content_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('create courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'position' => 'sometimes|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $content = Content::where('lesson_id', $lesson_id)
            ->where('id', $content_id)
            ->firstOrFail();

        $content->update($request->only(['title', 'description', 'is_active', 'position']));

        // Clear cache
        Cache::forget("content_{$lesson_id}");
        for ($i = 1; $i <= $content->total_pages; $i++) {
            Cache::forget("content_{$lesson_id}_page_{$i}");
        }

        return response()->json([
            'message' => 'Content updated successfully',
            'data' => new ContentResource($content)
        ]);
    }

    /**
     * Re-process content (re-paginate, re-optimize)
     */
    public function reprocess(int $course_id, int $module_id, int $lesson_id, int $content_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('create courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'words_per_page' => 'nullable|integer|min:100|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $content = Content::where('lesson_id', $lesson_id)
                ->where('id', $content_id)
                ->firstOrFail();

            if (!$content->source_file_path) {
                return response()->json([
                    'message' => 'No source file available for reprocessing'
                ], 400);
            }

            $fullSourcePath = storage_path('app/public/' . $content->source_file_path);

            if (!file_exists($fullSourcePath)) {
                return response()->json([
                    'message' => 'Source file not found'
                ], 404);
            }

            // Set new pagination if provided
            if ($request->filled('words_per_page')) {
                $this->documentConverter->setWordsPerPage($request->input('words_per_page'));
            }

            // Re-convert document
            $converted = $this->documentConverter->convertDocument($fullSourcePath, $content->original_filename);

            // Update content
            $content->update([
                'html_content' => $converted['html_content'],
                'json_content' => $converted['json_content'],
                'images' => $converted['images'],
                'assets' => $converted['assets'],
                'total_pages' => $converted['total_pages'],
                'words_per_page' => $request->input('words_per_page', $content->words_per_page),
                'metadata' => $converted['metadata'],
                'is_processed' => true,
                'processed_at' => now()
            ]);

            // Clear all caches
            Cache::forget("content_{$lesson_id}");
            for ($i = 1; $i <= $content->total_pages; $i++) {
                Cache::forget("content_{$lesson_id}_page_{$i}");
            }

            return response()->json([
                'message' => 'Content reprocessed successfully',
                'data' => new ContentResource($content)
            ]);

        } catch (\Exception $e) {
            Log::error('Content reprocessing failed', [
                'content_id' => $content_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to reprocess content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete content
     */
    public function delete(int $course_id, int $module_id, int $lesson_id, int $content_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('create courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $content = Content::where('lesson_id', $lesson_id)
            ->where('id', $content_id)
            ->firstOrFail();

        // Delete source file if exists
        if ($content->source_file_path) {
            Storage::disk('public')->delete($content->source_file_path);
        }

        // Delete uploaded images
        if ($content->images) {
            foreach ($content->images as $image) {
                if (isset($image['processed_path']) && $image['type'] === 'uploaded') {
                    Storage::disk('public')->delete(str_replace('storage/', '', $image['processed_path']));
                }
            }
        }

        $content->delete();

        // Clear cache
        Cache::forget("content_{$lesson_id}");
        for ($i = 1; $i <= $content->total_pages; $i++) {
            Cache::forget("content_{$lesson_id}_page_{$i}");
        }

        return response()->json([
            'message' => 'Content deleted successfully'
        ]);
    }

    /**
     * Get content metadata only (lightweight)
     */
    public function getMetadata(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $content = Content::where('lesson_id', $lesson_id)
            ->where('is_active', true)
            ->select(['id', 'lesson_id', 'title', 'description', 'type', 'total_pages', 'metadata', 'created_at', 'updated_at'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $content->id,
                'lesson_id' => $content->lesson_id,
                'title' => $content->title,
                'description' => $content->description,
                'type' => $content->type,
                'total_pages' => $content->total_pages,
                'metadata' => $content->metadata,
                'created_at' => $content->created_at,
                'updated_at' => $content->updated_at
            ]
        ]);
    }

    /**
     * Serve individual content files (images, assets)
     */
    public function serveFile(int $course_id, int $module_id, int $lesson_id, string $filepath, Request $request)
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get the content to verify access
        $content = Content::where('lesson_id', $lesson_id)
            ->where('is_active', true)
            ->firstOrFail();

        // Security: Prevent directory traversal attacks
        $filepath = str_replace(['../', '..\\'], '', $filepath);
        
        // Try to find the file in the content's image or asset paths
        $possiblePaths = [
            storage_path('app/public/uploads/contents/images/' . $filepath),
            storage_path('app/public/uploads/contents/assets/' . $filepath),
            storage_path('app/public/' . $filepath),
        ];

        foreach ($possiblePaths as $fullPath) {
            if (file_exists($fullPath) && is_file($fullPath)) {
                $mimeType = mime_content_type($fullPath);
                
                // Track file access
                $this->trackActivity($request, $content, 'file_access', ['filepath' => $filepath]);
                
                return response()->file($fullPath, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
                ]);
            }
        }

        Log::warning('File not found', [
            'lesson_id' => $lesson_id,
            'filepath' => $filepath,
            'searched_paths' => $possiblePaths
        ]);

        return response()->json(['message' => 'File not found'], 404);
    }

    /**
     * Track user activity
     */
    private function trackActivity(Request $request, $content, string $action, array $additionalMetadata = []): void
    {
        try {
            \App\Models\UserActivity::create([
                'user_id' => $request->user()->id,
                'activity_type' => 'content',
                'activity_id' => $content?->id ?? ($additionalMetadata['lesson_id'] ?? null),
                'action' => $action,
                'last_seen_url' => $request->fullUrl(),
                'last_seen_at' => now(),
                'metadata' => array_merge([
                    'content_title' => $content?->title ?? null,
                    'content_type' => $content?->type ?? null,
                ], $additionalMetadata),
                'device_type' => $this->detectDeviceType($request),
                'user_agent' => $request->header('User-Agent'),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track content activity', [
                'action' => $action,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
}
