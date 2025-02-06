<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Cache;

class PostsController extends Controller
{
    /**
     * Store a newly created post.
     *
     * @param StorePostRequest $request The validated request data.
     * @return JsonResponse The created post.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'nullable|exists:categories,id',
                'published_at' => 'nullable|date',
                'status' => 'required|in:draft,published,scheduled,archived'
            ]);

            $status = $validated['status'];

            if (isset($validated['published_at'])) {
                $publishDate = Carbon::parse($validated['published_at']);
                $status = $publishDate->isFuture() ? 'scheduled' : 'published';
            }

            $post = Post::create([
                'user_id' => Auth::id(),
                'category_id' => $validated['category_id'] ?? null,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'published_at' => $validated['published_at'] ?? null,
                'status' => $status
            ]);

            $this->clearCache();

            return response()->json(['message' => 'Post created successfully', 'post' => $post], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating post', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of posts with optional filters.
     *
     * Available filters:
     * - `author_id`: Filter by author's ID.
     * - `category_id`: Filter by category.
     * - `search`: Filter by title or content.
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse The filtered list of posts.
     */
    public function index(Request $request)
    {
        try {
            $query = Post::query()
                ->where('status', '!=', 'archived')
                ->where(function ($query) {
                    $query->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });

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
                return $query->with(['author', 'category'])
                    ->orderBy('published_at', 'desc')
                    ->paginate(15);
            });

            return response()->json($posts);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching posts', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified post.
     *
     * @param Post $post The post instance to display.
     * @return JsonResponse The post details.
     */
    public function show(Request $request)
    {
        try {
            $request->validate(['post_id' => 'required|exists:posts,id']);
            $postId = $request->post_id;

            $post = Cache::tags(['post_' . $postId])->remember('post_' . $postId, 3600, function () use ($postId) {
                return Post::with(['author', 'category'])->findOrFail($postId);
            });

            return response()->json($post);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Post not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error retrieving post', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing post.
     *
     * @param UpdatePostRequest $request The validated request data.
     * @param Post $post The post instance to update.
     * @return JsonResponse The updated post.
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'post_id' => 'required|exists:posts,id',
                'title' => 'string|max:255',
                'content' => 'string',
                'category_id' => 'nullable|exists:categories,id',
                'published_at' => 'nullable|date',
                'status' => 'in:draft,published,scheduled,archived'
            ]);

            $post = Post::findOrFail($request->post_id);

            if ($post->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $this->handleStatusChanges($post, $validated);
            $post->update($validated);

            $this->clearCache($post->id);

            return response()->json(['message' => 'Post updated successfully', 'post' => $post]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Post not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error updating post', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified post.
     *
     * @param Post $post The post instance to delete.
     * @return JsonResponse A confirmation message.
     */
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

    private function handleStatusChanges(Post $post, array $validated): array
    {
        if (isset($validated['published_at'])) {
            $publishDate = Carbon::parse($validated['published_at']);
            $validated['status'] = $publishDate->isFuture() ? 'scheduled' : 'published';
        }

        if ($validated['status'] === 'archived') {
            $validated['published_at'] = null; // Fix: Use $validated instead of modifying $post directly
        }

        return $validated;
    }

    private function clearCache(?int $postId = null): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags(['posts_index'])->flush();
            if ($postId) {
                Cache::tags(["post_{$postId}"])->flush();
            }
        } else {
            Cache::forget('posts_index'); // Fallback if cache tags are not supported
            if ($postId) {
                Cache::forget("post_{$postId}");
            }
        }
    }
}
