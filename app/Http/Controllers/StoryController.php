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

        $formattedStories = $stories->map(function ($story) {
            return [
                'id' => $story->id,
                'title' => $story->title,
                'content' => $story->content,
                'category' => [
                    'id' => $story->category->id,
                    'name' => $story->category->name,
                ],
                'user' => [
                    'id' => $story->user->id,
                    'username' => $story->user->name,
                    'imagelink' => $story->user->imageLink,
                ],
                'images' => $story->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'story_id' => $image->story_id,
                        'path' => $image->path,
                    ];
                }),
            ];
        }); 

        return response()->json([
            'data' => $formattedStories,
        ]);
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

    public function getImagesByStoryId($id)
    {
        $story = Story::with('images')->findOrFail($id);

        return response()->json([
            'story_id' => $story->id,
            'title' => $story->title,
            'content' => $story->content,
            'images' => $story->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'path' => $image->path,
                    'created_at' => $image->created_at,
                ];
            }),
        ]);
    }   

    public function getMyStories(Request $request)
    {
        
        $userId = auth()->id();

        $stories = Story::with(['category', 'images'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->paginate(4);

        if ($stories->isEmpty()) {
            return response()->json(['message' => 'Belum ada story yang ditambahkan.'], 200);
        }

        return response()->json($stories, 200);
    }


    public function getStoriesAscending()
    {
        $stories = Story::with(['category', 'user', 'images'])
            ->orderBy('title', 'asc') // Mengurutkan berdasarkan judul secara ascending (A-Z)
            ->get();

        return response()->json(['data' => $stories], 200);
    }

    public function getStoriesDescending()
    {   
        $stories = Story::with(['category', 'user', 'images'])
            ->orderBy('title', 'desc') // Mengurutkan berdasarkan judul secara descending (Z-A)
            ->get();

        return response()->json(['data' => $stories], 200);
    }

    public function getSimilarStories($storyId)
    {
        // Ambil story berdasarkan ID, termasuk relasi kategori
        $currentStory = Story::with('category')->find($storyId);

        // Jika story tidak ditemukan, kembalikan pesan error
        if (!$currentStory) {
            return response()->json(['message' => 'Story tidak ditemukan.'], 404);
        }

        // Ambil story lain yang memiliki kategori sama dengan story ini
        $similarStories = Story::with(['category', 'user', 'images'])
            ->where('category_id', $currentStory->category_id) // Cari berdasarkan kategori yang sama
            ->where('id', '!=', $storyId) // Kecualikan story yang sedang dilihat
            ->orderBy('created_at', 'desc') // Urutkan dari yang terbaru
            ->paginate(3); // Batasi 3 per halaman

        // Jika tidak ada story yang mirip, kembalikan pesan kosong
        if ($similarStories->isEmpty()) {
            return response()->json(['message' => 'Tidak ada story serupa yang ditemukan.'], 200);
        }

        return response()->json($similarStories, 200);
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
