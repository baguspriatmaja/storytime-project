<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');

        $categoryQuery = Category::query();

        if ($keyword) {
            $categoryQuery->where('name', 'like', "%{$keyword}%");
        }

        $categories = $categoryQuery->orderBy('id', 'asc')->get();

        $response = [
            'data' => $categories
        ];

        return response()->json($response);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required']
        ], [
            'name.required' => 'Nama Category wajib diisi.',
        ]);

        $category = Category::create($validatedData);
        return response()->json([
            'message' => 'Category berhasil disimpan',
            'category' => $category
        ], 201);
    }

    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->all());
        return response()->json(['message' => 'Category berhasil diupdate'], 200);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category berhasil dihapus'], 200);
    }

    public function getAllCategoriesWithStories()
    {
        $categories = Category::with(['stories' => function ($query) {
            $query->orderBy('id', 'asc');
        }])->get();

        $response = $categories->map(function ($category) {
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'stories' => $category->stories->map(function ($story) {
                    return [
                        'story_id' => $story->id,
                        'title' => $story->title,
                        'username' => $story->user->username,
                        'content' => $story->content,
                        'cover' => $story->images[0],
                        'author_img' => $story->user->imageLink,
                        'created_at' => $story->created_at->toDateTimeString(),
                    ];
                }),
            ];
        });

        return response()->json($response, 200);
    }
}
