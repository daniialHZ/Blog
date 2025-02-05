<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Cache;

class PostsController extends Controller
{
    // 1️⃣ Create a new post
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'nullable|exists:categories,id',
            ]);

            $post = Post::create([
                'user_id' => Auth::id(),
                'category_id' => $validated['category_id'] ?? null,
                'title' => $validated['title'],
                'content' => $validated['content'],
            ]);

            // Clear index cache
            Cache::tags(['posts_index'])->flush();

            return response()->json(['message' => 'Post created successfully', 'post' => $post], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating post', 'error' => $e->getMessage()], 500);
        }
    }

    // 2️⃣ Get all posts (with filters)
    public function index(Request $request)
    {
        try {
            $query = Post::query();

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', "%{$request->search}%")
                        ->orWhere('content', 'like', "%{$request->search}%");
                });
            }

            $cacheKey = 'posts_index_' . md5(serialize($request->query()));
            $posts = Cache::tags(['posts_index'])->remember($cacheKey, 3600, function () use ($query) {
                return $query->latest()->get();
            });

            return response()->json($posts);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching posts', 'error' => $e->getMessage()], 500);
        }
    }

    // 3️⃣ Show a single post
    public function show(Request $request)
    {
        try {
            $request->validate(['post_id' => 'required|exists:posts,id']);
            $postId = $request->post_id;

            $post = Cache::tags(['post_' . $postId])->remember('post_' . $postId, 3600, function () use ($postId) {
                return Post::findOrFail($postId);
            });

            return response()->json($post);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Post not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error retrieving post', 'error' => $e->getMessage()], 500);
        }
    }

    // 4️⃣ Update a post
    public function update(Request $request)
    {
        try {
            $request->validate([
                'post_id' => 'required|exists:posts,id',
                'title' => 'string|max:255',
                'content' => 'string',
                'category_id' => 'nullable|exists:categories,id',
            ]);

            $post = Post::findOrFail($request->post_id);

            if ($post->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $post->update($request->only(['title', 'content', 'category_id']));

            // Clear relevant caches
            Cache::tags(['posts_index'])->flush();
            Cache::tags(['post_' . $post->id])->flush();

            return response()->json(['message' => 'Post updated successfully', 'post' => $post]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Post not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error updating post', 'error' => $e->getMessage()], 500);
        }
    }

    // 5️⃣ Delete a post
    public function destroy(Request $request)
    {
        try {
            $request->validate(['post_id' => 'required|exists:posts,id']);

            $post = Post::findOrFail($request->post_id);

            if ($post->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $post->delete();

            // Clear relevant caches
            Cache::tags(['posts_index'])->flush();
            Cache::tags(['post_' . $post->id])->flush();

            return response()->json(['message' => 'Post deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Post not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error deleting post', 'error' => $e->getMessage()], 500);
        }
    }
}
