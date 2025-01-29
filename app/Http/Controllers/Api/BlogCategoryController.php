<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = 10;
        if ($request->query('limit')) {
            $limit = $request->query('limit');
        }
        $skip = 0;
        if ($request->query('skip')) {
            $skip = $request->query('skip');
        }
        // first level category
        $parent = 0;
        if ($request->query('parent')) {
            $parent = $request->query('parent');
        }
        // search by name
        $name = '';
        if ($request->query('name')) {
            $name = $request->query('name');
        }
        $categories = BlogCategory::where('name', 'LIKE', "%$name%")->where('parent', $parent)->orderBy('order')->skip($skip)->take($limit)->get();
        $total_count = BlogCategory::where('name', 'LIKE', "%$name%")->where('parent', $parent)->count();

        // create response pagination
        $previous_page = [];
        if ($skip > 0) {
            $previous_page = [
                'limit' => $limit,
                'skip' => $skip - $limit,
            ];
        }
        $next_page = [];
        if ($skip + $limit < $total_count) {
            $next_page = [
                'limit' => $limit,
                'skip' => $skip + $limit,
            ];
        }
        if ($total_count > 0) {
            return response()->json([
                'message' => 'Founded items',
                'total_count' => $total_count,
                'pagination' => [
                    'next_page' => $next_page,
                    'previous_page' => $previous_page,

                ],
                'data' => BlogCategoryResource::collection($categories)
            ], 200);
        } else {
            return response()->json([
                'message' => 'No record available',
                'total_count' => $total_count,
                'data' => []
            ], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'parent' => 'nullable|integer',
        ]);

        $blogCategory = BlogCategory::create($fields);

        return response()->json([
            'message' => 'Item created successfully',
            'data' => new BlogCategoryResource($blogCategory)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $blogCategory = BlogCategory::where('id', $request->category)->first();
        if ($blogCategory) {
            return response()->json([
                'message' => 'Item information',
                'data' => new BlogCategoryResource($blogCategory)
            ], 200);
        } else {
            return response()->json([
                'message' => 'Item not found',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $blogCategory = BlogCategory::where('id', $request->category)->first();
        if ($blogCategory) {
            $fields = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'order' => 'nullable|integer',
                'parent' => 'nullable|integer',
            ]);
            $blogCategory->update($fields);
            return response()->json([
                'message' => 'Item updated successfully.',
                'data' => new BlogCategoryResource($blogCategory)
            ], 200);
        } else {
            return response()->json([
                'message' => 'Item not found',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $blogCategory = BlogCategory::where('id', $request->category)->first();
        if ($blogCategory) {
            //delete children
            BlogCategory::where('parent', $blogCategory->id)->delete();
            //delete category
            $blogCategory->delete();
            return response()->json([
                'message' => 'item deleted successfully.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Item not found',
            ], 404);
        }
    }
}
