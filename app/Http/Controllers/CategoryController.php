<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');

        $category = Category::query();

        if($keyword) {
            $category = $category->where('name', 'like', "%{$keyword}%");
        }

        $category = $category->orderBy('id', 'asc')->paginate(5);
        return response()->json($category);
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
}
