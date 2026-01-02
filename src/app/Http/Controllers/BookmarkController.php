<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * Toggle bookmark for a resource.
     */
    public function toggleBookmark(Request $request, $type, $id)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        // Map type to model class
        $modelMap = [
            'lesson' => Lesson::class,
            'course' => Course::class,
            'module' => Module::class,
        ];

        if (!isset($modelMap[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid bookmarkable type. Use: lesson, course, or module',
            ], 400);
        }

        $modelClass = $modelMap[$type];

        // Check if resource exists
        if (!$modelClass::find($id)) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($type) . ' not found',
            ], 404);
        }

        $bookmark = Bookmark::where([
            'user_id' => auth()->id(),
            'bookmarkable_type' => $modelClass,
            'bookmarkable_id' => $id,
        ])->first();

        if ($bookmark) {
            // Remove bookmark
            $bookmark->delete();
            
            return response()->json([
                'success' => true,
                'bookmarked' => false,
                'message' => 'Bookmark removed',
            ]);
        } else {
            // Add bookmark
            $bookmark = Bookmark::create([
                'user_id' => auth()->id(),
                'bookmarkable_type' => $modelClass,
                'bookmarkable_id' => $id,
                'note' => $validated['note'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'bookmarked' => true,
                'data' => [
                    'id' => $bookmark->id,
                    'note' => $bookmark->note,
                    'created_at' => $bookmark->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        }
    }

    /**
     * Get all bookmarks for authenticated user.
     */
    public function getMyBookmarks(Request $request)
    {
        $type = $request->query('type');

        $bookmarksQuery = Bookmark::where('user_id', auth()->id())
            ->with('bookmarkable')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $modelMap = [
                'lesson' => Lesson::class,
                'course' => Course::class,
                'module' => Module::class,
            ];

            if (isset($modelMap[$type])) {
                $bookmarksQuery->where('bookmarkable_type', $modelMap[$type]);
            }
        }

        $bookmarks = $bookmarksQuery->get();

        $data = $bookmarks->map(function ($bookmark) {
            $item = $bookmark->bookmarkable;
            
            if (!$item) {
                return null;
            }

            $typeMap = [
                Lesson::class => 'lesson',
                Course::class => 'course',
                Module::class => 'module',
            ];

            $type = $typeMap[$bookmark->bookmarkable_type] ?? 'unknown';

            $result = [
                'id' => $bookmark->id,
                'type' => $type,
                'resource' => [
                    'id' => $item->id,
                    'title' => $item->title,
                ],
                'note' => $bookmark->note,
                'bookmarked_at' => $bookmark->created_at->format('Y-m-d H:i:s'),
            ];

            // Add course context for lessons
            if ($type === 'lesson' && $item->module) {
                $result['resource']['course'] = $item->module->course->title ?? null;
                $result['resource']['module'] = $item->module->title ?? null;
            }

            return $result;
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $data->count(),
        ]);
    }

    /**
     * Update bookmark note.
     */
    public function updateBookmark(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $bookmark = Bookmark::where('user_id', auth()->id())->findOrFail($id);
        $bookmark->update(['note' => $validated['note']]);

        return response()->json([
            'success' => true,
            'data' => $bookmark,
        ]);
    }

    /**
     * Delete a bookmark.
     */
    public function deleteBookmark($id)
    {
        $bookmark = Bookmark::where('user_id', auth()->id())->findOrFail($id);
        $bookmark->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bookmark deleted',
        ]);
    }

    /**
     * Check if resource is bookmarked.
     */
    public function checkBookmark($type, $id)
    {
        $modelMap = [
            'lesson' => Lesson::class,
            'course' => Course::class,
            'module' => Module::class,
        ];

        if (!isset($modelMap[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type',
            ], 400);
        }

        $bookmark = Bookmark::where([
            'user_id' => auth()->id(),
            'bookmarkable_type' => $modelMap[$type],
            'bookmarkable_id' => $id,
        ])->first();

        return response()->json([
            'success' => true,
            'bookmarked' => $bookmark !== null,
            'bookmark' => $bookmark ? [
                'id' => $bookmark->id,
                'note' => $bookmark->note,
                'created_at' => $bookmark->created_at->format('Y-m-d H:i:s'),
            ] : null,
        ]);
    }
}
