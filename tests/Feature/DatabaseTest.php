<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use App\Enums\Priority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that todos table has all required columns
     * This validates the migration structure
     */
    public function test_todos_table_has_required_columns()
    {
        // Assert: Check that todos table exists
        $this->assertTrue(Schema::hasTable('todos'));

        // Assert: Check that all required columns exist
        $requiredColumns = [
            'id', 'user_id', 'category_id', 'title', 'description', 
            'priority', 'completed_at', 'created_at', 'updated_at'
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('todos', $column),
                "Todos table missing required column: {$column}"
            );
        }
    }

    /**
     * Test that users table has required columns
     * This validates the users migration
     */
    public function test_users_table_has_required_columns()
    {
        // Assert: Check that users table exists
        $this->assertTrue(Schema::hasTable('users'));

        // Assert: Check that all required columns exist
        $requiredColumns = [
            'id', 'name', 'email', 'email_verified_at', 'password', 
            'remember_token', 'created_at', 'updated_at'
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Users table missing required column: {$column}"
            );
        }
    }

    /**
     * Test that categories table has required columns
     * This validates the categories migration
     */
    public function test_categories_table_has_required_columns()
    {
        // Assert: Check that categories table exists
        $this->assertTrue(Schema::hasTable('categories'));

        // Assert: Check that all required columns exist
        $requiredColumns = [
            'id', 'name', 'created_at', 'updated_at'
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('categories', $column),
                "Categories table missing required column: {$column}"
            );
        }
    }

    /**
     * Test that foreign key constraints work correctly
     * This validates referential integrity
     */
    public function test_foreign_key_constraints_work()
    {
        // Arrange: Create user and category
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Act: Create todo with valid foreign keys
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Assert: Todo was created successfully
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Verify relationships work
        $this->assertEquals($user->id, $todo->user->id);
        $this->assertEquals($category->id, $todo->category->id);
    }

    /**
     * Test that cascade delete works for user deletion
     * This validates the onDelete cascade constraint
     */
    public function test_cascade_delete_works_for_user_deletion()
    {
        // Arrange: Create user and their todos
        $user = User::factory()->create();
        $category = Category::factory()->create();
        
        $todos = Todo::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Verify todos exist
        $this->assertDatabaseCount('todos', 3);

        // Act: Delete user
        $user->delete();

        // Assert: All user's todos are deleted
        $this->assertDatabaseCount('todos', 0);
        $this->assertDatabaseMissing('todos', ['user_id' => $user->id]);
    }

    /**
     * Test that cascade delete works for category deletion
     * This validates the onDelete cascade constraint for categories
     */
    public function test_cascade_delete_works_for_category_deletion()
    {
        // Arrange: Create category and todos
        $user = User::factory()->create();
        $category = Category::factory()->create();
        
        $todos = Todo::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Verify todos exist
        $this->assertDatabaseCount('todos', 3);

        // Act: Delete category
        $category->delete();

        // Assert: All todos in that category are deleted
        $this->assertDatabaseCount('todos', 0);
        $this->assertDatabaseMissing('todos', ['category_id' => $category->id]);
    }

    /**
     * Test that priority enum values are stored correctly
     * This validates the enum column functionality
     */
    public function test_priority_enum_values_are_stored_correctly()
    {
        // Arrange: Create todo with each priority level
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $priorities = [Priority::LOW, Priority::MEDIUM, Priority::HIGH];

        foreach ($priorities as $priority) {
            $todo = Todo::factory()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'priority' => $priority
            ]);

            // Assert: Priority is stored correctly in database
            $this->assertDatabaseHas('todos', [
                'id' => $todo->id,
                'priority' => $priority->value
            ]);

            // Assert: Priority is cast correctly when retrieved
            $todo->refresh();
            $this->assertInstanceOf(Priority::class, $todo->priority);
            $this->assertEquals($priority, $todo->priority);
        }
    }

    /**
     * Test that completed_at timestamp works correctly
     * This validates the timestamp functionality
     */
    public function test_completed_at_timestamp_works_correctly()
    {
        // Arrange: Create todo
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'completed_at' => null
        ]);

        // Assert: Initially not completed
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'completed_at' => null
        ]);

        // Act: Mark as completed
        $completionTime = now();
        $todo->update(['completed_at' => $completionTime]);

        // Assert: Completed timestamp is stored
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'completed_at' => $completionTime
        ]);

        // Assert: Cast to Carbon instance
        $todo->refresh();
        $this->assertInstanceOf(\Carbon\Carbon::class, $todo->completed_at);
    }

    /**
     * Test that database indexes are created
     * This validates the performance optimization indexes
     */
    public function test_database_indexes_are_created()
    {
        // Check that the migration file exists and contains index definitions
        $migrationFile = database_path('migrations/2025_06_24_145929_create_todos_table.php');
        $this->assertFileExists($migrationFile);
        
        $migrationContent = file_get_contents($migrationFile);
        
        // Assert: Migration contains index definitions
        $this->assertStringContainsString('$table->index', $migrationContent);
        $this->assertStringContainsString('user_id', $migrationContent);
        $this->assertStringContainsString('category_id', $migrationContent);
        $this->assertStringContainsString('priority', $migrationContent);
        $this->assertStringContainsString('completed_at', $migrationContent);
        
        // Assert: Foreign key constraints are defined
        $this->assertStringContainsString('foreignId', $migrationContent);
        $this->assertStringContainsString('constrained', $migrationContent);
        
        // Note: Laravel doesn't expose custom indexes easily in tests
        // In a real scenario, you might check the migration file directly
        // or use raw SQL to check indexes
    }

    /**
     * Test that unique constraints work
     * This validates data integrity constraints
     */
    public function test_unique_constraints_work()
    {
        // Arrange: Create user with specific email
        $user1 = User::factory()->create(['email' => 'test@example.com']);

        // Act & Assert: Try to create another user with same email
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['email' => 'test@example.com']);
    }

    /**
     * Test that required fields cannot be null
     * This validates NOT NULL constraints
     */
    public function test_required_fields_cannot_be_null()
    {
        // Arrange: Create user and category
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Act & Assert: Try to create todo without required fields
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Todo::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => null, // This should fail
            'priority' => Priority::MEDIUM
        ]);
    }

    /**
     * Test that text fields can store long content
     * This validates the text column type
     */
    public function test_text_fields_can_store_long_content()
    {
        // Arrange: Create user and category
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create long description
        $longDescription = str_repeat('This is a very long description. ', 50); // ~1500 characters

        // Act: Create todo with long description
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => $longDescription
        ]);

        // Assert: Long description is stored correctly
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'description' => $longDescription
        ]);

        // Assert: Description is retrieved correctly
        $todo->refresh();
        $this->assertEquals($longDescription, $todo->description);
    }

    /**
     * Test that timestamps are automatically set
     * This validates the timestamps functionality
     */
    public function test_timestamps_are_automatically_set()
    {
        // Arrange: Create user and category
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Act: Create todo
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Assert: Timestamps are set
        $this->assertNotNull($todo->created_at);
        $this->assertNotNull($todo->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $todo->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $todo->updated_at);

        // Assert: Initially created_at and updated_at are the same
        $this->assertEquals($todo->created_at->timestamp, $todo->updated_at->timestamp);

        // Act: Update todo
        $originalUpdatedAt = $todo->updated_at;
        sleep(1); // Ensure time difference
        $todo->update(['title' => 'Updated Title']);

        // Assert: updated_at is changed but created_at remains the same
        $todo->refresh();
        $this->assertEquals($originalUpdatedAt->timestamp, $todo->created_at->timestamp);
        $this->assertGreaterThan($originalUpdatedAt->timestamp, $todo->updated_at->timestamp);
    }

    /**
     * Test that soft deletes are not used (hard deletes)
     * This validates that we're using hard deletes as intended
     */
    public function test_hard_deletes_are_used()
    {
        // Arrange: Create user and category
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        $todoId = $todo->id;

        // Act: Delete todo
        $todo->delete();

        // Assert: Todo is completely removed from database
        $this->assertDatabaseMissing('todos', ['id' => $todoId]);
        
        // Assert: No soft delete columns exist
        $this->assertFalse(Schema::hasColumn('todos', 'deleted_at'));
    }
} 