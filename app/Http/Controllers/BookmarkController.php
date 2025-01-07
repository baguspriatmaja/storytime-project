<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $bookmarks = Bookmark::all();
        return response()->json($bookmarks);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'story_id' => ['required']
        ]);

        $validatedData['user_id'] = auth()->id();

        $bookmark = Bookmark::create($validatedData);
        return response()->json([
            'message' => 'Bookmark berhasil disimpan',
            'bookmark' => $bookmark
        ], 201);
    }

    public function show(string $id)
    {
        $bookmark = Bookmark::findOrFail($id);
        return response()->json($bookmark);
    }

    public function destroy(string $id)
    {
        $bookmark = Bookmark::findOrFail($id);
        $bookmark->delete();
        return response()->json(['message' => 'Bookmark berhasil dihapus'], 200);
    }
}
