<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOutlet;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ResolvesOutlet;

    public function index(Request $request)
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->orderBy('name', 'asc')
            ->get();
        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $category = Category::where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ], 200);
    }



    public function store(Request $request)
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $request->validate([
            'name' => [
                'required',
                'min:3',
                // unique per user_id & outlet
                Rule::unique('categories', 'name')->where(function ($q) use ($user, $outletId) {
                    return $q->where('user_id', $user->id)
                        ->where('outlet_id', $outletId)
                        ->whereNull('deleted_at');
                }),
            ],
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $category = new Category();
        $category->user_id = $user->id;
        $category->outlet_id = $outletId;
        $category->name = $request->name;

        if ($request->hasFile('image')) {
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }
        $category->save();
        return response()->json([
            'success' => true,
            'message' => 'Category Created',
            'data' => $category
        ], 201);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        // pastikan hanya bisa update kategori miliknya
        $category = Category::where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->findOrFail($request->id);

        $request->validate([
            'id' => ['required', Rule::exists('categories', 'id')->where(function ($q) use ($user, $outletId) {
                return $q->where('user_id', $user->id)
                    ->where('outlet_id', $outletId)
                    ->whereNull('deleted_at');
            })],
            'name' => [
                'required',
                'min:3',
                // unique per user_id, abaikan id kategori yang sedang diupdate
                Rule::unique('categories', 'name')
                    ->where(fn ($q) => $q
                        ->where('user_id', $user->id)
                        ->where('outlet_id', $outletId)
                        ->whereNull('deleted_at'))
                    ->ignore($category->id),
            ],
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $category->name = $request->name;
        if ($request->hasFile('image')) {
            Storage::delete('public/categories/' . $category->image);
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }
        $category->save();
        return response()->json([
            'success' => true,
            'message' => 'Category Updated',
            'data' => $category
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        // pastikan hanya bisa menghapus miliknya
        $category = Category::where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->findOrFail($id);

        if (!empty($category->image) && Storage::disk('public')->exists('categories/' . $category->image)) {
            Storage::disk('public')->delete('categories/' . $category->image);
        }
        
        $category->delete();
        return response()->json([
            'success' => true,
            'message' => 'Category Deleted',
            'data' => $category
        ], 200);
    }
}
