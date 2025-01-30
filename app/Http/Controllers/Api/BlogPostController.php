<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlogPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BlogPost::query();
        $limit = 10;
        if ($request->query('limit')) {
            $limit = $request->query('limit');
        }
        $skip = 0;
        if ($request->query('skip')) {
            $skip = $request->query('skip');
        }

        // search by title and content
        if ($request->query('search')) {
            $search = $request->query('search');
            $query->where('title', 'LIKE', "%$search%")->orWhere('content', 'LIKE', "%$search%");
        }

        if ($request->query('published_before')) {
            $published_before = $request->query('published_before');
            $query->where('published_at', '<=', $published_before);
        }
        if ($request->query('is_draft')) {
            $is_draft = $request->query('is_draft');
            $query->where('is_draft',  $is_draft);
        }
        if ($request->query('is_published')) {
            $is_published = $request->query('is_published');
            $query->where('is_published',  $is_published);
        }
        if ($request->query('user')) {
            $user = $request->query('user');
            $query->where('user',  $user);
        }
        if ($request->query('category')) {
            $category = $request->query('category');
            $query->where('category',  $category);
        }


        $posts = $query->skip($skip)->take($limit)->get();
        $total_count = $query->count();

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
                'data' => BlogPostResource::collection($posts)
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
     * Display a listing of the latest post.
     */
    public function latest(Request $request)
    {
        $posts = Cache::remember('posts', 300, function () {
            return BlogPost::whereDate('is_published', '<=', Carbon::today())->orderBy('created_at', 'desc')->take(20)->get();
        });
        return response()->json([
            'message' => 'Founded items',
            'data' => BlogPostResource::collection($posts)
        ], 200);
    }

    /**
     * Published posts total count
     */
    public function publishedPostsCount(Request $request)
    {
        $postsCount = Cache::remember('postsCount', 300, function () {
            return BlogPost::whereDate('is_published', '<=', Carbon::today())->count();
        });
        return response()->json([
            'message' => 'Published posts total count',
            'data' => $postsCount
        ], 200);
    }



    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_draft' => 'required|boolean',
            'is_published' => 'required|boolean',
            'published_at' => 'required|date',
            'user' => 'required|exists:users,id', // we can set user from $request->user()
            'category' => 'required|exists:blog_categories,id',
        ]);

        $blogPost = BlogPost::create($fields);

        return response()->json([
            'message' => 'Item created successfully',
            'data' => new BlogPostResource($blogPost)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $blogPost = BlogPost::where('id', $request->post)->first();
        if ($blogPost) {
            return response()->json([
                'message' => 'Item information',
                'data' => new BlogPostResource($blogPost)
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
        $blogPost = BlogPost::where('id', $request->post)->first();
        if ($blogPost) {
            $fields = $request->validate([
                'title' => 'string|max:255',
                'content' => 'string',
                'is_draft' => 'boolean',
                'is_published' => 'boolean',
                'published_at' => 'date',
                'category' => 'exists:blog_categories,id',
            ]);
            $blogPost->update($fields);
            return response()->json([
                'message' => 'Item updated successfully.',
                'data' => new BlogPostResource($blogPost)
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
        $blogPost = BlogPost::where('id', $request->post)->first();
        if ($blogPost) {
            $blogPost->delete();
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
