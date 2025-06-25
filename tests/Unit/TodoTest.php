<?php

namespace Tests\Unit;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use App\Enums\Priority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a todo can be marked as completed
     * This tests the markAsCompleted() method and isCompleted() helper
     */
    public function test_todo_can_be_marked_as_completed()
    {
        // Arrange: Create a todo that is not completed
        $todo = Todo::factory()->create([
            'completed_at' => null
        ]);

        // Act: Mark the todo as completed
        $todo->markAsCompleted();

        // Assert: Check that the todo is now completed
        $this->assertTrue($todo->isCompleted());
        $this->assertNotNull($todo->completed_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $todo->completed_at);
    }

    /**
     * Test that a todo can be marked as incomplete
     * This tests the markAsIncomplete() method
     */
    public function test_todo_can_be_marked_as_incomplete()
    {
        // Arrange: Create a todo that is completed
        $todo = Todo::factory()->create([
            'completed_at' => now()
        ]);

        // Act: Mark the todo as incomplete
        $todo->markAsIncomplete();

        // Assert: Check that the todo is now incomplete
        $this->assertFalse($todo->isCompleted());
        $this->assertNull($todo->completed_at);
    }

    /**
     * Test that todo belongs to a user
     * This tests the user relationship
     */
    public function test_todo_belongs_to_user()
    {
        // Arrange: Create a user and todo
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        // Act & Assert: Check the relationship
        $this->assertInstanceOf(User::class, $todo->user);
        $this->assertEquals($user->id, $todo->user->id);
    }

    /**
     * Test that todo belongs to a category
     * This tests the category relationship
     */
    public function test_todo_belongs_to_category()
    {
        // Arrange: Create a category and todo
        $category = Category::factory()->create();
        $todo = Todo::factory()->create(['category_id' => $category->id]);

        // Act & Assert: Check the relationship
        $this->assertInstanceOf(Category::class, $todo->category);
        $this->assertEquals($category->id, $todo->category->id);
    }

    /**
     * Test completed scope returns only completed todos
     * This tests the scopeCompleted() method
     */
    public function test_completed_scope_returns_completed_todos()
    {
        // Arrange: Create completed and incomplete todos
        $completedTodo = Todo::factory()->create(['completed_at' => now()]);
        $incompleteTodo = Todo::factory()->create(['completed_at' => null]);

        // Act: Get completed todos using scope
        $completedTodos = Todo::completed()->get();

        // Assert: Only completed todos should be returned
        $this->assertTrue($completedTodos->contains($completedTodo));
        $this->assertFalse($completedTodos->contains($incompleteTodo));
        $this->assertEquals(1, $completedTodos->count());
    }

    /**
     * Test incomplete scope returns only incomplete todos
     * This tests the scopeIncomplete() method
     */
    public function test_incomplete_scope_returns_incomplete_todos()
    {
        // Arrange: Create completed and incomplete todos
        $completedTodo = Todo::factory()->create(['completed_at' => now()]);
        $incompleteTodo = Todo::factory()->create(['completed_at' => null]);

        // Act: Get incomplete todos using scope
        $incompleteTodos = Todo::incomplete()->get();

        // Assert: Only incomplete todos should be returned
        $this->assertTrue($incompleteTodos->contains($incompleteTodo));
        $this->assertFalse($incompleteTodos->contains($completedTodo));
        $this->assertEquals(1, $incompleteTodos->count());
    }

    /**
     * Test byCategory scope filters todos by category
     * This tests the scopeByCategory() method
     */
    public function test_by_category_scope_filters_todos()
    {
        // Arrange: Create categories and todos
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        $todo1 = Todo::factory()->create(['category_id' => $category1->id]);
        $todo2 = Todo::factory()->create(['category_id' => $category2->id]);

        // Act: Filter todos by category1
        $filteredTodos = Todo::byCategory($category1->id)->get();

        // Assert: Only todos from category1 should be returned
        $this->assertTrue($filteredTodos->contains($todo1));
        $this->assertFalse($filteredTodos->contains($todo2));
        $this->assertEquals(1, $filteredTodos->count());
    }

    /**
     * Test byPriority scope filters todos by priority
     * This tests the scopeByPriority() method
     */
    public function test_by_priority_scope_filters_todos()
    {
        // Arrange: Create todos with different priorities
        $highPriorityTodo = Todo::factory()->create(['priority' => Priority::HIGH]);
        $lowPriorityTodo = Todo::factory()->create(['priority' => Priority::LOW]);

        // Act: Filter todos by HIGH priority
        $filteredTodos = Todo::byPriority(Priority::HIGH->value)->get();

        // Assert: Only HIGH priority todos should be returned
        $this->assertTrue($filteredTodos->contains($highPriorityTodo));
        $this->assertFalse($filteredTodos->contains($lowPriorityTodo));
        $this->assertEquals(1, $filteredTodos->count());
    }

    /**
     * Test that priority is cast to enum
     * This tests the priority casting
     */
    public function test_priority_is_cast_to_enum()
    {
        // Arrange: Create a todo with HIGH priority
        $todo = Todo::factory()->create(['priority' => Priority::HIGH]);

        // Act & Assert: Check that priority is an enum instance
        $this->assertInstanceOf(Priority::class, $todo->priority);
        $this->assertEquals(Priority::HIGH, $todo->priority);
    }

    /**
     * Test that completed_at is cast to datetime
     * This tests the completed_at casting
     */
    public function test_completed_at_is_cast_to_datetime()
    {
        // Arrange: Create a completed todo
        $completedAt = now();
        $todo = Todo::factory()->create(['completed_at' => $completedAt]);

        // Act & Assert: Check that completed_at is a Carbon instance
        $this->assertInstanceOf(\Carbon\Carbon::class, $todo->completed_at);
        $this->assertEquals($completedAt->timestamp, $todo->completed_at->timestamp);
    }
} 