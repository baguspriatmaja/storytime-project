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
