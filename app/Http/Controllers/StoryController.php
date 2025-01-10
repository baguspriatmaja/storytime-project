<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
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

        $stories = $stories->orderBy('id', 'asc')->get();

        $response = [
            'data' => $stories
        ];
        return response()->json($response);
    }

    public function getLatestStory()
    {
        $stories = Story::with(['category','user']);

        $stories = $stories->orderBy('id', 'asc')->paginate(6);
        return response()->json($stories);
    }


    public function getStoriesByCategory($categoryId)
    {
        $stories = Story::with(['category', 'user'])
            ->where('category_id', $categoryId)
            ->orderBy('id', 'asc')
            ->get();

        if ($stories->isEmpty()) {
            return response()->json(['message' => 'Tidak ada story untuk kategori ini.'], 404);
        }

        $response = [
            'data' => $stories
        ];

        return response()->json($response, 200);
    }

    public function getNewestStory()
    {
        $stories = Story::with(['category','user']);

        $stories = $stories->orderBy('id', 'asc')->paginate(12);
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
        ], [
            'category_id.required' => 'Id category wajib diisi.',
            'title.required' => 'Nama judul wajib diisi.',
            'content.required' => 'Isi content wajib diisi.',
            'content_image.required' => 'Gambar wajib diunggah.',
            'content_image.max' => 'Maksimal 5 gambar diperbolehkan.',
        ]);

        $validatedData['user_id'] = auth()->id();

        $imagePaths = [];
        if ($request->hasFile('content_image')) {
            foreach ($request->file('content_image') as $index => $file) {
                $fileName = time() . "_{$index}_" . $file->getClientOriginalName();
                $filePath = $file->storeAs('stories_images', $fileName, 'public');

                $imagePaths[] = [
                    'id' => $index + 1,
                    'path' => $filePath
                ];
            }
        }

        $validatedData['content_image'] = json_encode($imagePaths);

        $story = Story::create($validatedData);

        return response()->json([
            'message' => 'Story berhasil disimpan',
            'story' => $story
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

        $validatedData = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'content_image' => ['nullable', 'array'],
            'content_image.*' => ['file', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'remove_image' => ['nullable', 'integer'],
        ]);

        $existingImages = json_decode($story->content_image, true) ?? [];

        
        if ($request->has('remove_image')) {
            $removeImageId = $request->input('remove_image');
            $existingImages = array_filter($existingImages, function ($image) use ($removeImageId) {
                return $image['id'] != $removeImageId;
            });

            foreach ($existingImages as $image) {
                if ($image['id'] == $removeImageId) {
                    Storage::disk('public')->delete($image['path']);
                }
            }
        }

        if ($request->hasFile('content_image')) {
            foreach ($request->file('content_image') as $index => $file) {
                if (count($existingImages) >= 5) {
                    return response()->json([
                        'message' => 'Maksimal 5 gambar diperbolehkan.'
                    ], 400);
                }

                $fileName = time() . "_{$index}_" . $file->getClientOriginalName();
                $filePath = $file->storeAs('stories_images', $fileName, 'public');

                $newId = count($existingImages) + 1;

                $existingImages[] = [
                    'id' => $newId,
                    'path' => $filePath
                ];
            }
        }

        $validatedData['content_image'] = json_encode(array_values($existingImages));

        $story->update($validatedData);

        return response()->json([
            'message' => 'Story berhasil diupdate',
            'story' => $story
        ], 200);
    } 

    public function destroy(string $id)
    {
        $story = Story::findOrFail($id);
        $story->delete();

        return response()->json(['message' => 'Story berhasil dihapus'], 200);
    }

}   
