<?php
namespace App\Http\Controllers;

use App\Http\Requests\EpubCreateRequest;
use App\Http\Requests\EpubUpdateRequest;
use App\Http\Resources\EpubResource;
use App\Models\Epub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $data = $request->validated();
        $data['lesson_id'] = $lesson_id;
        
        // Handle file upload if present
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\-\.]/', '', $originalName);
            
            // Store in public disk so it's accessible
            $filePath = $file->storeAs('uploads/epubs', $fileName, 'public');
            $data['file_path'] = $filePath;
            
            // Log for debugging
            \Log::info('File uploaded successfully', [
                'original_name' => $originalName,
                'stored_path' => $filePath,
                'file_size' => $file->getSize()
            ]);
        } else {
            // Log if no file or invalid file
            if ($request->hasFile('file')) {
                \Log::warning('Invalid file uploaded', [
                    'file_error' => $request->file('file')->getError()
                ]);
            }
        }

        $epub = Epub::create($data);

        // Cache management
        Cache::forget("lesson_{$lesson_id}");
        Cache::put("epub_{$epub->id}", $epub, 3600);
        Cache::forget("epubs_list_{$lesson_id}");

        return (new EpubResource($epub))->response()->setStatusCode(201);
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

        return (new EpubResource($epub))->response()->setStatusCode(200);
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
            \Log::info('File deleted successfully', [
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
}

