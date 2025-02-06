<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $bookmark = Bookmark::where('user_id', $request->user()->id)->with('story')->get();

        return response()->json([
            'bookmarks' => $bookmark
        ], 200);
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'story_id' => 'required|exists:stories,id'
        ]);

        $userId = $request->user()->id;

        $existingBookmark = Bookmark::where('user_id', $userId)
            ->where('story_id', $validated['story_id'])
            ->first();

        if ($existingBookmark) {
            return response()->json([
                'success' => false,
                'message' => 'Story ini sudah ada bookmark.'
            ], 409);
        }

        $bookmark = Bookmark::create([
            'user_id' => $userId,
            'story_id' => $validated['story_id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bookmark Berhasil Ditambahkan!',
            'bookmark' => $bookmark
        ], 201);
    }

    public function getMyBookmarks(Request $request)
    {
        $userId = auth()->id();

        $bookmarks = Bookmark::with(['story'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->paginate(12);

        if ($bookmarks->isEmpty()) {
            return response()->json(['message' => 'Belum ada Bookmark yang ditambahkan.'], 200);
        }

        $formattedStories = $bookmarks->map(function ($bookmark) {
            return [
                'bookmark_id' => $bookmark->id,
                'user_id' => $bookmark->user_id,
                'category_id' => $bookmark->story->category_id,
                'story_id' => $bookmark->story_id,
                'title' => $bookmark->story->title,
                'content' => $bookmark->story->content,
                'created_at' => $bookmark->story->created_at->toDateTimeString(),
                'category' => [
                    'category_id' => $bookmark->story->category->id,
                    'name' => $bookmark->story->category->name,
                    'created_at' => $bookmark->story->category->created_at->toDateTimeString(),
                    'updated_at' => $bookmark->story->category->updated_at->toDateTimeString(),
                ],
                'images' => $bookmark->story->images->map(function ($image) {
                    return [
                        'image_id' => $image->id,
                        'story_id' => $image->story_id,
                        'path' => $image->path,
                        'created_at' => $image->created_at->toDateTimeString(),
                        'updated_at' => $image->updated_at->toDateTimeString(),
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $formattedStories,
            'pagination' => [
                'first_page_url' => $bookmarks->url(1),
                'from' => $bookmarks->firstItem(),
                'last_page' => $bookmarks->lastPage(),
                'last_page_url' => $bookmarks->url($bookmarks->lastPage()),
                'links' => $bookmarks->links(),
                'next_page_url' => $bookmarks->nextPageUrl(),
                'path' => $bookmarks->path(),
                'per_page' => $bookmarks->perPage(),
                'prev_page_url' => $bookmarks->previousPageUrl(),
                'to' => $bookmarks->lastItem(),
                'total' => $bookmarks->total(),
                'current_page' => $bookmarks->currentPage(),
            ],
        ]);
    }

    public function show(string $id)
    {
        $bookmark = Bookmark::with('story')->findOrFail($id);

        return response()->json([
            'bookmark' => $bookmark
        ], 200);
    }

    public function destroy(string $id)
    {
        $bookmark = Bookmark::findOrFail($id);

        $bookmark->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bookmark Berhasil Dihapus!'
        ], 200);
    }
}
