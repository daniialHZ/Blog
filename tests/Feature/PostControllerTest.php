<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

test('create post', function () {
    $user = User::firstOrFail();
    $token = $user->createToken('TestToken')->plainTextToken;

    $category = Category::create(['name' => 'Test Post 10']);

    $response = $this->postJson('/api/posts/create', [
        'title' => 'My First Post',
        'content' => 'This is the content of my first post.',
        'category_id' => $category->id,
        'status' => 'published',
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Post created successfully',
        ])
        ->assertJsonStructure(['message', 'post' => ['id', 'title', 'content', 'status']]);

    $this->assertDatabaseHas('posts', [
        'title' => 'My First Post',
        'content' => 'This is the content of my first post.',
        'status' => 'published',
    ]);
});

test('get all posts', function () {
    $response = $this->postJson('/api/posts/list');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

test('get post by id', function () {
    $user = User::firstOrFail();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Technology 122']);
    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'My First Post',
        'content' => 'This is the content of my first post.',
        'category_id' => $category->id,
        'status' => 'published',
    ]);

    $response = $this->postJson('/api/posts/show', ['post_id' => $post->id]);

    $response->assertStatus(200)
        ->assertJsonStructure(['id', 'title', 'content', 'status']);
});

test('update post', function () {
    $user = User::firstOrFail();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Technology 4423']);
    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'My First Post',
        'content' => 'This is the content of my first post.',
        'category_id' => $category->id,
        'status' => 'published',
    ]);

    $response = $this->postJson('/api/posts/update', [
        'post_id' => $post->id,
        'title' => 'Updated Post Title',
        'content' => 'Updated content of the post.',
        'status' => 'draft',
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Post updated successfully']);

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Post Title',
        'content' => 'Updated content of the post.',
    ]);
});

test('delete post', function () {
    $user = User::firstOrFail();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Test Poset 2323']);
    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'My First Post',
        'content' => 'This is the content of my first post.',
        'category_id' => $category->id,
        'status' => 'published',
    ]);

    $response = $this->postJson('/api/posts/delete', ['post_id' => $post->id]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Post deleted successfully']);

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});
