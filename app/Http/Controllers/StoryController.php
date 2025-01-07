<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $categoryId = $request->input('category_id');

        $stories = Story::with(['category','user']);

        if($keyword) {
            $stories = $stories->where('title', 'like', "%{$keyword}%");
        }

        if($categoryId) {
            $stories = $stories->where('category_id', $categoryId);
        }

        $stories = $stories->orderBy('title', 'asc')->paginate(5);
        return response()->json($stories);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category_id' => ['required'],
            'title' => ['required'],
            'content' => ['required'],
            'content_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ], [
            'category_id.required' => 'Id category wajib diisi.',
            'title.required' => 'Nama judul wajib diisi.',
            'content.required' => 'Isi content wajib diisi.',
            'content_image.required' => 'Foto content wajib diisi',
        ]);

        $validatedData['user_id'] = auth()->id();

        $stories = Story::create($validatedData);
        return response()->json([
            'message' => 'Content berhasil disimpan',
            'story' => $stories
        ], 201);
    }

    public function show(string $id)
    {
        $stories = Story::findOrFail($id);
        return response()->json($stories);
    }

    public function update(Request $request, string $id)
    {
        $story = Story::findOrFail($id);
        $story->update($request->all());
        return response()->json(['message' => 'Story berhasil diupdate'], 200);
    }

    public function destroy(string $id)
    {
        $story = Story::findOrFail($id);
        $story->delete();

        return response()->json(['message' => 'Story berhasil dihapus'], 200);
    }
}
