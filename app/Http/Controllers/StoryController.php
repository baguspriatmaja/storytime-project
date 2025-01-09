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

        if ($request->hasFile('content_image')) {
            $file = $request->file('content_image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('stories_images', $fileName, 'public');
    
            $validatedData['content_image'] = $filePath; // Simpan path file ke database
        }

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
        // Temukan story berdasarkan ID
        $story = Story::findOrFail($id);

        // Validasi request, sesuaikan dengan kebutuhan Anda
        $validatedData = $request->validate([
            'title' => ['nullable'],
            'content' => ['nullable'],
            'content_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        // Periksa apakah ada file 'content_image' yang diunggah
        if ($request->hasFile('content_image')) {
            $file = $request->file('content_image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('stories_images', $fileName, 'public');

            // Update field 'content_image' dengan path file baru
            $validatedData['content_image'] = $filePath;

            // Hapus file lama jika ada
            if ($story->content_image && \Storage::disk('public')->exists($story->content_image)) {
                \Storage::disk('public')->delete($story->content_image);
            }
        }

        // Update story dengan data yang sudah divalidasi
        $story->update($validatedData);

        // Kembalikan response
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

    public function getByCategory($categoryId)
    {
        // Ambil stories berdasarkan category_id
        $stories = Story::with(['category', 'user'])
            ->where('category_id', $categoryId)
            ->orderBy('title', 'asc')
            ->paginate(5); // Sesuaikan pagination jika perlu

        // Cek apakah data ditemukan
        if ($stories->isEmpty()) {
            return response()->json(['message' => 'Tidak ada story untuk kategori ini.'], 404);
        }

        return response()->json($stories, 200);
    }

}
