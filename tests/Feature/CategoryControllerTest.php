<?php

use App\Models\Category;
use App\Models\User;

test('create category', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    $response = $this->postJson('/api/categories/create', [
        'name' => 'Technology',
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Category created successfully',
        ])
        ->assertJsonStructure(['category' => ['id', 'name']]);

    // Ensure the category is created in the database
    $this->assertDatabaseHas('categories', [
        'name' => 'Technology',
    ]);
});

test('create category with parent', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    // First, create a parent category
    $parentCategory = Category::create(['name' => 'Tech', 'parent_id' => null]);

    // Now, create a child category under the parent
    $response = $this->postJson('/api/categories/create', [
        'name' => 'Programming',
        'parent_id' => $parentCategory->id,
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Category created successfully',
        ])
        ->assertJsonStructure(['message', 'category' => ['id', 'name', 'parent_id']]);

    // Ensure the child category is created with the correct parent_id
    $this->assertDatabaseHas('categories', [
        'name' => 'Programming',
        'parent_id' => $parentCategory->id,
    ]);
});

test('get all categories', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    $response = $this->postJson('/api/categories/list', ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(200)
        ->assertJsonStructure([['id', 'name', 'subcategories']]);
});

test('get category by id', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    // Create a category with subcategories
    $parentCategory = Category::create(['name' => 'Tech']);
    $childCategory = Category::create(['name' => 'Programming', 'parent_id' => $parentCategory->id]);

    $response = $this->postJson("/api/categories/show", [
        'category_id' => $parentCategory->id
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(200)
        ->assertJsonStructure(['id', 'name', 'subcategories' => [[ 'id', 'name']]]);
});

test('update category', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    $category = Category::create(['name' => 'Technology 11']);

    $response = $this->postJson("/api/categories/update", [
        'category_id' => $category->id,
        'name' => 'Updated Technology',
        'parent_id' => null,
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Category updated successfully', 'category' => ['name' => 'Updated Technology']]);

    // Ensure the category was updated in the database
    $this->assertDatabaseHas('categories', [
        'name' => 'Updated Technology',
    ]);
});

test('update category with parent change', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    // Create two categories, one to be the parent
    $parentCategory = Category::create(['name' => 'Tech 22']);
    $childCategory = Category::create(['name' => 'Programming 22', 'parent_id' => null]);

    // Update the child category to have a new parent
    $response = $this->postJson("/api/categories/update", [
        'category_id' => $childCategory->id,
        'name' => 'Advanced Programming 22',
        'parent_id' => $parentCategory->id,
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Category updated successfully', 'category' => ['parent_id' => $parentCategory->id]]);

    // Ensure the category's parent_id was updated
    $this->assertDatabaseHas('categories', [
        'id' => $childCategory->id,
        'parent_id' => $parentCategory->id,
    ]);
});

test('delete category', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    $category = Category::create(['name' => 'Outdated Tech']);

    $response = $this->postJson("/api/categories/delete", [
        'category_id' => $category->id
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Category deleted successfully']);

    // Ensure the category is deleted from the database
    $this->assertDatabaseMissing('categories', [
        'name' => 'Outdated Tech',
    ]);
});

test('delete category with subcategories', function () {
    // Authenticate user
    $user = User::firstOrFail(); // Get the first user from the database
    $token = $user->createToken('TestToken')->plainTextToken;

    // Create a parent category and a child category
    $parentCategory = Category::create(['name' => 'Science']);
    $childCategory = Category::create(['name' => 'Physics', 'parent_id' => $parentCategory->id]);

    $response = $this->postJson("/api/categories/delete", [
        'category_id' => $parentCategory->id
    ], ['Authorization' => 'Bearer ' . $token]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Category deleted successfully']);

    // Ensure both parent and child categories are deleted from the database
    $this->assertDatabaseMissing('categories', [
        'id' => $parentCategory->id,
    ]);
    $this->assertDatabaseMissing('categories', [
        'id' => $childCategory->id,
    ]);
});
