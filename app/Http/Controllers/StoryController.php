<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Story;
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
                'story_id' => $story->id,
                'title' => $story->title,
                'content' => $story->content,
                'created_at' => $story->created_at->toDateTimeString(),
                'category' => [
                    'category_id' => $story->category->id,
                    'name' => $story->category->name,
                ],
                'user' => [
                    'user_id' => $story->user->id,
                    'username' => $story->user->name,
                    'imagelink' => $story->user->imageLink,
                ],
                'images' => $story->images->map(function ($image) {
                    return [
                        'image_id' => $image->id,
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
            ->orderBy('id', 'asc')
            ->paginate(6);

        return response()->json($stories);
    }

    public function getNewestStory()
    {
        $stories = Story::with(['category', 'user', 'images'])
            ->orderBy('created_at', 'asc')
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
                    'image_id' => $image->id,
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
            ->orderBy('title', 'desc') 
            ->get();

        return response()->json(['data' => $stories], 200);
    }

    public function getSimilarStories($storyId)
    {
        
        $currentStory = Story::with('category')->find($storyId);

        if (!$currentStory) {
            return response()->json(['message' => 'Story tidak ditemukan.'], 404);
        }

        $similarStories = Story::with(['category', 'user', 'images'])
            ->where('category_id', $currentStory->category_id) 
            ->where('id', '!=', $storyId) 
            ->orderBy('created_at', 'desc') 
            ->paginate(3); 

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

        $story = Story::create($validatedData);

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
        $story = Story::with('category', 'images', 'user')->findOrFail($id);

        $similarStories = Story::with(['category', 'user', 'images'])
            ->where('category_id', $story->category->id) 
            ->where('id', '!=', $story->id) 
            ->orderBy('created_at', 'desc') 
            ->take(3)
            ->get();

        return response()->json([
            'story_id' => $story->id,
            'category_id' => $story->category->id,
            'user_id' => $story->user->id,
            'username' => $story->user->username,
            'author_img' => $story->user->imageLink,
            'title' => $story->title,
            'content' => $story->content,
            'created_at' => $story->created_at->toDateTimeString(),
            'updated_at' => $story->updated_at->toDateTimeString(),
            'images' => $story->images,
            'similar_stories' => $similarStories->map(function ($similar) {
                return [
                    'story_id' => $similar->id,
                    'category_id' => $similar->category->id,
                    'user_id' => $similar->user->id,
                    'username' => $similar->user->username,
                    'title' => $similar->title,
                    'content' => $similar->content,
                    'created_at' => $similar->created_at->toDateTimeString(),   
                    'images' => $similar->images,
                ];
            }),
        ]);
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

        $story->update($validatedData);

        if ($request->has('remove_image')) {
            foreach ($request->input('remove_image') as $imageId) {
                $image = $story->images()->find($imageId);

                if ($image) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $image->path));

                    $image->delete();
                }
            }
        }

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

        foreach ($story->images as $image) {
            Storage::disk('public')->delete(str_replace('storage/', '', $image->path));
            $image->delete();
        }

        $story->delete();

        return response()->json(['message' => 'Story berhasil dihapus'], 200);
    }

}
