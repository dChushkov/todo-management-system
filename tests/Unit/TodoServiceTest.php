<?php

namespace Tests\Unit;

use App\Services\TodoService;
use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use App\Enums\Priority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TodoServiceTest extends TestCase
{
    use RefreshDatabase;

    private TodoService $todoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->todoService = app(TodoService::class);
    }

    /**
     * Test that getTodosWithCategory returns todos with eager loaded category
     * This validates that N+1 problem is avoided
     */
    public function test_get_todos_with_category_uses_eager_loading()
    {
        // Arrange: Create user, category and todos
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Todo::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Act: Get todos with category
        $todos = $this->todoService->getTodosWithCategory($user);

        // Assert: Check that todos are returned and category is eager loaded
        $this->assertEquals(3, $todos->total());
        $this->assertTrue($todos->items()[0]->relationLoaded('category'));
        $this->assertInstanceOf(Category::class, $todos->items()[0]->category);
    }

    /**
     * Test that getTodosWithCategory filters by category when provided
     * This tests the category filtering functionality
     */
    public function test_get_todos_with_category_filters_by_category()
    {
        // Arrange: Create user and categories
        $user = User::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // Create todos in different categories
        Todo::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category1->id
        ]);
        Todo::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category2->id
        ]);

        // Act: Filter by category1
        $todos = $this->todoService->getTodosWithCategory($user, $category1->id);

        // Assert: Only todos from category1 should be returned
        $this->assertEquals(2, $todos->total());
        $this->assertTrue(collect($todos->items())->every(fn($todo) => $todo->category_id === $category1->id));
    }

    /**
     * Test that getTodosWithCategory filters by status when provided
     * This tests the status filtering (completed/pending)
     */
    public function test_get_todos_with_category_filters_by_status()
    {
        // Arrange: Create user and todos with different statuses
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Todo::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'completed_at' => now() // completed
        ]);
        Todo::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'completed_at' => null // pending
        ]);

        // Act: Filter by completed status
        $completedTodos = $this->todoService->getTodosWithCategory($user, null, 'completed');
        $pendingTodos = $this->todoService->getTodosWithCategory($user, null, 'pending');

        // Assert: Correct filtering
        $this->assertEquals(2, $completedTodos->total());
        $this->assertEquals(3, $pendingTodos->total());
        $this->assertTrue(collect($completedTodos->items())->every(fn($todo) => $todo->isCompleted()));
        $this->assertTrue(collect($pendingTodos->items())->every(fn($todo) => !$todo->isCompleted()));
    }

    /**
     * Test that getTodosWithCategory filters by priority when provided
     * This tests the priority filtering
     */
    public function test_get_todos_with_category_filters_by_priority()
    {
        // Arrange: Create user and todos with different priorities
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Todo::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => Priority::HIGH
        ]);
        Todo::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => Priority::LOW
        ]);

        // Act: Filter by HIGH priority
        $highPriorityTodos = $this->todoService->getTodosWithCategory($user, null, null, Priority::HIGH->value);

        // Assert: Only HIGH priority todos should be returned
        $this->assertEquals(2, $highPriorityTodos->total());
        $this->assertTrue(collect($highPriorityTodos->items())->every(fn($todo) => $todo->priority === Priority::HIGH));
    }

    /**
     * Test that getTodosWithCategory respects pagination
     * This tests the pagination functionality
     */
    public function test_get_todos_with_category_respects_pagination()
    {
        // Arrange: Create user and multiple todos
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Todo::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Act: Get todos with pagination (5 per page)
        $todos = $this->todoService->getTodosWithCategory($user, null, null, null, 5);

        // Assert: Pagination works correctly
        $this->assertEquals(5, count($todos->items()));
        $this->assertEquals(5, $todos->perPage());
        $this->assertEquals(10, $todos->total());
        $this->assertEquals(2, $todos->lastPage());
    }

    /**
     * Test that createTodo creates a todo for the user
     * This tests the todo creation functionality
     */
    public function test_create_todo_creates_todo_for_user()
    {
        // Arrange: Create user and category
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $todoData = [
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'category_id' => $category->id,
            'priority' => Priority::HIGH
        ];

        // Act: Create todo
        $todo = $this->todoService->createTodo($user, $todoData);

        // Assert: Todo is created correctly
        $this->assertInstanceOf(Todo::class, $todo);
        $this->assertEquals($user->id, $todo->user_id);
        $this->assertEquals($todoData['title'], $todo->title);
        $this->assertEquals($todoData['description'], $todo->description);
        $this->assertEquals($todoData['category_id'], $todo->category_id);
        $this->assertEquals($todoData['priority'], $todo->priority);
    }

    /**
     * Test that updateTodo updates todo data
     * This tests the todo update functionality
     */
    public function test_update_todo_updates_todo_data()
    {
        // Arrange: Create todo
        $todo = Todo::factory()->create();
        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'priority' => Priority::LOW
        ];

        // Act: Update todo
        $result = $this->todoService->updateTodo($todo, $updateData);

        // Assert: Todo is updated correctly
        $this->assertTrue($result);
        $todo->refresh();
        $this->assertEquals($updateData['title'], $todo->title);
        $this->assertEquals($updateData['description'], $todo->description);
        $this->assertEquals($updateData['priority'], $todo->priority);
    }

    /**
     * Test that deleteTodo deletes the todo
     * This tests the todo deletion functionality
     */
    public function test_delete_todo_deletes_todo()
    {
        // Arrange: Create todo
        $todo = Todo::factory()->create();
        $todoId = $todo->id;

        // Act: Delete todo
        $result = $this->todoService->deleteTodo($todo);

        // Assert: Todo is deleted
        $this->assertTrue($result);
        $this->assertDatabaseMissing('todos', ['id' => $todoId]);
    }

    /**
     * Test that markAsCompleted marks todo as completed
     * This tests the completion functionality
     */
    public function test_mark_as_completed_marks_todo_completed()
    {
        // Arrange: Create incomplete todo
        $todo = Todo::factory()->create(['completed_at' => null]);

        // Act: Mark as completed
        $this->todoService->markAsCompleted($todo);

        // Assert: Todo is marked as completed
        $todo->refresh();
        $this->assertTrue($todo->isCompleted());
        $this->assertNotNull($todo->completed_at);
    }

    /**
     * Test that markAsIncomplete marks todo as incomplete
     * This tests the incompletion functionality
     */
    public function test_mark_as_incomplete_marks_todo_incomplete()
    {
        // Arrange: Create completed todo
        $todo = Todo::factory()->create(['completed_at' => now()]);

        // Act: Mark as incomplete
        $this->todoService->markAsIncomplete($todo);

        // Assert: Todo is marked as incomplete
        $todo->refresh();
        $this->assertFalse($todo->isCompleted());
        $this->assertNull($todo->completed_at);
    }

    /**
     * Test that getStats returns correct statistics
     * This tests the statistics functionality with raw SQL
     */
    public function test_get_stats_returns_correct_statistics()
    {
        // Arrange: Create user and todos with different statuses
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['name' => 'Work']);
        $category2 = Category::factory()->create(['name' => 'Personal']);

        // Create todos in different categories and statuses
        Todo::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'completed_at' => now() // completed
        ]);
        Todo::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'completed_at' => null // pending
        ]);
        Todo::factory()->count(1)->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'completed_at' => now() // completed
        ]);

        // Act: Get statistics
        $stats = $this->todoService->getStats($user);

        // Assert: Statistics are correct
        $this->assertArrayHasKey('category_stats', $stats);
        $this->assertArrayHasKey('priority_stats', $stats);
        $this->assertArrayHasKey('total_todos', $stats);
        $this->assertArrayHasKey('completed_todos', $stats);
        $this->assertArrayHasKey('pending_todos', $stats);

        $this->assertEquals(6, $stats['total_todos']);
        $this->assertEquals(4, $stats['completed_todos']);
        $this->assertEquals(2, $stats['pending_todos']);

        // Check category stats
        $workStats = collect($stats['category_stats'])->firstWhere('category_name', 'Work');
        if ($workStats) {
            $this->assertEquals(5, $workStats->total_todos);
            $this->assertEquals(3, $workStats->completed_todos);
            $this->assertEquals(2, $workStats->pending_todos);
        }
    }
} 