<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Story;
use App\Models\StoryImages;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $categoryId = $request->input('category_id');

        $stories = Story::with(['category', 'user', 'images']);

        if ($keyword) {
            $stories->where('title', 'like', "%{$keyword}%");
        }

        if ($categoryId) {
            $stories->where('category_id', $categoryId);
        }

        $stories = $stories->orderBy('id', 'asc')->get();

        return response()->json(['data' => $stories]);
    }

    public function getLatestStory()
    {
        $stories = Story::with(['category', 'user', 'images'])
            ->orderBy('id', 'desc')
            ->paginate(6);

        return response()->json($stories);
    }

    public function getNewestStory()
    {
        $stories = Story::with(['category', 'user', 'images'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json($stories);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'content_image' => ['required', 'array', 'max:5'],
            'content_image.*' => ['file', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $validatedData['user_id'] = auth()->id();

        // Buat story
        $story = Story::create($validatedData);

        // Simpan gambar
        if ($request->hasFile('content_image')) {
            foreach ($request->file('content_image') as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = $file->storeAs('stories_images', $fileName, 'public');

                $story->images()->create([
                    'path' => "storage/$filePath"
                ]);
            }
        }

        return response()->json([
            'message' => 'Story berhasil disimpan',
            'story' => $story->load('images'),
        ], 201);
    }

    public function show(string $id)
    {
        $story = Story::with('images')->findOrFail($id);

        return response()->json($story);
    }

    public function update(Request $request, string $id)
    {
        $story = Story::findOrFail($id);

        $validatedData = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'content_image' => ['nullable', 'array', 'max:5'],
            'content_image.*' => ['file', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'remove_image' => ['nullable', 'array'],
            'remove_image.*' => ['integer'], // ID gambar yang akan dihapus
        ]);

        // Update data story
        $story->update($validatedData);

        // Hapus gambar tertentu jika diminta
        if ($request->has('remove_image')) {
            foreach ($request->input('remove_image') as $imageId) {
                $image = $story->images()->find($imageId);

                if ($image) {
                    // Hapus file dari storage
                    Storage::disk('public')->delete(str_replace('storage/', '', $image->path));

                    // Hapus dari database
                    $image->delete();
                }
            }
        }

        // Tambah gambar baru jika ada
        if ($request->hasFile('content_image')) {
            $existingImagesCount = $story->images()->count();

            foreach ($request->file('content_image') as $file) {
                if ($existingImagesCount >= 5) {
                    return response()->json(['message' => 'Maksimal 5 gambar diperbolehkan.'], 400);
                }

                $fileName = $file->getClientOriginalName();
                $filePath = $file->storeAs('stories_images', $fileName, 'public');

                $story->images()->create([
                    'path' => "storage/$filePath"
                ]);

                $existingImagesCount++;
            }
        }

        return response()->json([
            'message' => 'Story berhasil diupdate',
            'story' => $story->load('images'),
        ], 200);
    }

    public function destroy(string $id)
    {
        $story = Story::findOrFail($id);

        // Hapus semua gambar terkait
        foreach ($story->images as $image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $image->path));
            $image->delete();
        }

        // Hapus story
        $story->delete();

        return response()->json(['message' => 'Story berhasil dihapus'], 200);
    }
}
