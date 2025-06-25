<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use App\Enums\Priority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * Test that user can create a new todo via API
     * This tests the POST /api/todos endpoint
     */
    public function test_user_can_create_todo()
    {
        // Arrange: Prepare todo data
        $todoData = [
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'priority' => Priority::HIGH->value
        ];

        // Act: Create todo via API
        $response = $this->actingAs($this->user)
            ->postJson('/api/todos', $todoData);

        // Assert: Check response and database
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'todo' => [
                    'id', 'title', 'description', 'priority', 'priority_label',
                    'priority_color', 'category', 'created_at'
                ]
            ])
            ->assertJson([
                'message' => 'Todo created successfully',
                'todo' => [
                    'title' => 'Test Todo',
                    'description' => 'Test Description',
                    'priority' => Priority::HIGH->value
                ]
            ]);

        // Check database
        $this->assertDatabaseHas('todos', [
            'user_id' => $this->user->id,
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'priority' => Priority::HIGH->value
        ]);
    }

    /**
     * Test that user cannot create todo without required fields
     * This tests validation for the POST /api/todos endpoint
     */
    public function test_user_cannot_create_todo_without_required_fields()
    {
        // Act: Try to create todo without required fields
        $response = $this->actingAs($this->user)
            ->postJson('/api/todos', []);

        // Assert: Validation errors
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'category_id', 'priority']);
    }

    /**
     * Test that user can retrieve their todos via API
     * This tests the GET /api/todos endpoint
     */
    public function test_user_can_retrieve_their_todos()
    {
        // Arrange: Create todos for the user
        $todos = Todo::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        // Act: Get todos via API
        $response = $this->actingAs($this->user)
            ->getJson('/api/todos');

        // Assert: Check response structure and data
        $response->assertStatus(200)
            ->assertJsonStructure([
                'todos' => [
                    '*' => [
                        'id', 'title', 'description', 'priority', 'priority_label',
                        'priority_color', 'completed_at', 'is_completed', 'category',
                        'created_at', 'updated_at'
                    ]
                ],
                'pagination' => [
                    'current_page', 'last_page', 'per_page', 'total'
                ]
            ]);

        $this->assertCount(3, $response->json('todos'));
    }

    /**
     * Test that user can filter todos by category
     * This tests the category filtering functionality
     */
    public function test_user_can_filter_todos_by_category()
    {
        // Arrange: Create categories and todos
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Todo::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $category1->id
        ]);
        Todo::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id
        ]);

        // Act: Filter by category1
        $response = $this->actingAs($this->user)
            ->getJson("/api/todos?category={$category1->id}");

        // Assert: Only todos from category1 are returned
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('todos'));
        $this->assertTrue(
            collect($response->json('todos'))->every(
                fn ($todo) =>
                $todo['category']['id'] === $category1->id
            )
        );
    }

    /**
     * Test that user can filter todos by status (completed/pending)
     * This tests the status filtering functionality
     */
    public function test_user_can_filter_todos_by_status()
    {
        // Arrange: Create todos with different statuses
        Todo::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'completed_at' => now() // completed
        ]);
        Todo::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'completed_at' => null // pending
        ]);

        // Act: Filter by completed status
        $completedResponse = $this->actingAs($this->user)
            ->getJson('/api/todos?status=completed');

        // Act: Filter by pending status
        $pendingResponse = $this->actingAs($this->user)
            ->getJson('/api/todos?status=pending');

        // Assert: Correct filtering
        $completedResponse->assertStatus(200);
        $pendingResponse->assertStatus(200);

        $this->assertCount(2, $completedResponse->json('todos'));
        $this->assertCount(3, $pendingResponse->json('todos'));

        // Check that all returned todos have correct status
        $this->assertTrue(
            collect($completedResponse->json('todos'))->every(
                fn ($todo) =>
                $todo['is_completed'] === true
            )
        );
        $this->assertTrue(
            collect($pendingResponse->json('todos'))->every(
                fn ($todo) =>
                $todo['is_completed'] === false
            )
        );
    }

    /**
     * Test that user can retrieve a specific todo
     * This tests the GET /api/todos/{todo} endpoint
     */
    public function test_user_can_retrieve_specific_todo()
    {
        // Arrange: Create a todo
        $todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        // Act: Get specific todo
        $response = $this->actingAs($this->user)
            ->getJson("/api/todos/{$todo->id}");

        // Assert: Check response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'todo' => [
                    'id', 'title', 'description', 'priority', 'priority_label',
                    'priority_color', 'completed_at', 'is_completed', 'category',
                    'created_at', 'updated_at'
                ]
            ])
            ->assertJson([
                'todo' => [
                    'id' => $todo->id,
                    'title' => $todo->title
                ]
            ]);
    }

    /**
     * Test that user cannot access another user's todo
     * This tests authorization for the GET /api/todos/{todo} endpoint
     */
    public function test_user_cannot_access_other_user_todo()
    {
        // Arrange: Create another user and their todo
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->category->id
        ]);

        // Act: Try to access other user's todo
        $response = $this->actingAs($this->user)
            ->getJson("/api/todos/{$todo->id}");

        // Assert: Access denied
        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    /**
     * Test that user can update their todo
     * This tests the PUT /api/todos/{todo} endpoint
     */
    public function test_user_can_update_todo()
    {
        // Arrange: Create a todo
        $todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'category_id' => $this->category->id,
            'priority' => Priority::LOW->value
        ];

        // Act: Update todo
        $response = $this->actingAs($this->user)
            ->putJson("/api/todos/{$todo->id}", $updateData);

        // Assert: Check response and database
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo updated successfully',
                'todo' => [
                    'title' => 'Updated Title',
                    'description' => 'Updated Description',
                    'priority' => Priority::LOW->value
                ]
            ]);

        // Check database
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'priority' => Priority::LOW->value
        ]);
    }

    /**
     * Test that user can delete their todo
     * This tests the DELETE /api/todos/{todo} endpoint
     */
    public function test_user_can_delete_todo()
    {
        // Arrange: Create a todo
        $todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        // Act: Delete todo
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/todos/{$todo->id}");

        // Assert: Check response and database
        $response->assertStatus(200)
            ->assertJson(['message' => 'Todo deleted successfully']);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    /**
     * Test that user can mark todo as completed
     * This tests the PATCH /api/todos/{todo}/complete endpoint
     */
    public function test_user_can_mark_todo_as_completed()
    {
        // Arrange: Create an incomplete todo
        $todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'completed_at' => null
        ]);

        // Act: Mark as completed
        $response = $this->actingAs($this->user)
            ->patchJson("/api/todos/{$todo->id}/complete");

        // Assert: Check response and database
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo marked as completed'
            ])
            ->assertJsonStructure([
                'message', 'completed_at'
            ]);

        // Check database
        $todo->refresh();
        $this->assertTrue($todo->isCompleted());
        $this->assertNotNull($todo->completed_at);
    }

    /**
     * Test that user can mark todo as incomplete
     * This tests the PATCH /api/todos/{todo}/incomplete endpoint
     */
    public function test_user_can_mark_todo_as_incomplete()
    {
        // Arrange: Create a completed todo
        $todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'completed_at' => now()
        ]);

        // Act: Mark as incomplete
        $response = $this->actingAs($this->user)
            ->patchJson("/api/todos/{$todo->id}/incomplete");

        // Assert: Check response and database
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo marked as incomplete',
                'completed_at' => null
            ]);

        // Check database
        $todo->refresh();
        $this->assertFalse($todo->isCompleted());
        $this->assertNull($todo->completed_at);
    }

    /**
     * Test that user can get todo statistics
     * This tests the GET /api/todos/stats endpoint
     */
    public function test_user_can_get_todo_statistics()
    {
        // Arrange: Create todos with different statuses
        Todo::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'completed_at' => now() // completed
        ]);
        Todo::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'completed_at' => null // pending
        ]);

        // Act: Get statistics
        $response = $this->actingAs($this->user)
            ->getJson('/api/todos/stats');

        // Assert: Check response structure and data
        $response->assertStatus(200)
            ->assertJsonStructure([
                'category_stats',
                'total_todos',
                'completed_todos',
                'pending_todos'
            ])
            ->assertJson([
                'total_todos' => 5,
                'completed_todos' => 3,
                'pending_todos' => 2
            ]);
    }

    /**
     * Test that user can get categories
     * This tests the GET /api/categories endpoint
     */
    public function test_user_can_get_categories()
    {
        // Arrange: Create categories
        $categories = Category::factory()->count(3)->create();

        // Act: Get categories
        $response = $this->actingAs($this->user)
            ->getJson('/api/categories');

        // Assert: Check response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'categories' => [
                    '*' => ['id', 'name']
                ]
            ]);

        // Check that our newly created categories are in the response
        $responseCategories = $response->json('categories');
        $this->assertGreaterThanOrEqual(3, count($responseCategories));

        // Verify our specific categories exist
        foreach ($categories as $category) {
            $this->assertTrue(
                collect($responseCategories)->contains('id', $category->id),
                "Category {$category->name} not found in response"
            );
        }
    }

    /**
     * Test pagination functionality
     * This tests that todos are properly paginated
     */
    public function test_todos_are_paginated()
    {
        // Arrange: Create many todos
        Todo::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        // Act: Get todos with default pagination (5 per page)
        $response = $this->actingAs($this->user)
            ->getJson('/api/todos');

        // Assert: Check pagination
        $response->assertStatus(200);
        $pagination = $response->json('pagination');

        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(5, $pagination['per_page']);
        $this->assertEquals(15, $pagination['total']);
        $this->assertEquals(3, $pagination['last_page']);
        $this->assertCount(5, $response->json('todos'));

        // Test second page
        $response2 = $this->actingAs($this->user)
            ->getJson('/api/todos?page=2');

        $response2->assertStatus(200);
        $this->assertEquals(2, $response2->json('pagination.current_page'));
    }
}
