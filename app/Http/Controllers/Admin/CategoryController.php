<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\SlugService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Categories');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SlugService $slugService)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ], [
            'name.unique' => 'Kategori sudah terdaftar.',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $category = new Category();
        $category->name = $request->name;
        $category->slug = $slugService->createSlug(Category::class, $request->name);
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil ditambahkan',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category, SlugService $slugService)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ], [
            'name.unique' => 'Kategori sudah terdaftar.',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $category->name = $request->name;
        $category->slug = $slugService->createSlug(Category::class, $request->name);
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus',
        ]);
    }

    /**
     * Get all categories data.
     */
    public function categoryData(Request $request)
    {
        $query = Category::query();

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        // Handle sorting
        if ($request->has('sort')) {
            $sortField = $request->sort;
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
        }

        // Handle pagination
        $perPage = $request->input('per_page', 10);
        $categories = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($categories->currentPage() - 1) * $categories->perPage() + 1;
        $to = min($from + $categories->count() - 1, $categories->total());

        $transformedCategories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ];
        });

        return response()->json([
            'data' => $transformedCategories,
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
