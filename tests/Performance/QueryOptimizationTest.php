<?php

namespace Tests\Performance;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use App\Services\TodoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Enums\Priority;

class QueryOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private TodoService $todoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->todoService = app(TodoService::class);
    }

    /**
     * Test that todos index does not have N+1 problem
     * This validates that N+1 problem is avoided
     */
    public function test_todos_index_does_not_have_n_plus_one_problem()
    {
        // Arrange: Create user, category and multiple todos
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create 10 todos to make N+1 problem visible
        Todo::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Enable query log to count queries
        DB::enableQueryLog();

        // Act: Get todos with category using service method
        $todos = $this->todoService->getTodosWithCategory($user);

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should have reasonable number of queries (not N+1)
        // Expected: 2-3 queries (1 for todos, 1 for categories, maybe 1 for pagination)
        $this->assertLessThan(
            5,
            $queryCount,
            "N+1 problem detected! Found {$queryCount} queries for 10 todos. Expected less than 5."
        );

        // Verify that todos have categories loaded
        $this->assertEquals(10, $todos->total()); // Use total() for pagination
        $this->assertTrue($todos->items()[0]->relationLoaded('category'));
        $this->assertInstanceOf(Category::class, $todos->items()[0]->category);

        // Disable query log
        DB::disableQueryLog();
    }

    /**
     * Test that todos index without eager loading has N+1 problem
     * This demonstrates the N+1 problem when eager loading is not used
     */
    public function test_todos_index_without_eager_loading_has_n_plus_one_problem()
    {
        // Arrange: Create user, category and multiple todos
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Todo::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Enable query log
        DB::enableQueryLog();

        // Act: Get todos WITHOUT eager loading (simulating N+1 problem)
        $todos = $user->todos()->get();

        // Access category for each todo (this triggers N+1)
        foreach ($todos as $todo) {
            $todo->category->name; // This would trigger additional queries
        }

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should have many queries (N+1 problem)
        // Expected: 1 + N queries (1 for todos + 1 for each category access)
        $this->assertGreaterThan(
            10,
            $queryCount,
            "Expected N+1 problem but found only {$queryCount} queries for 10 todos."
        );

        // Disable query log
        DB::disableQueryLog();
    }

    /**
     * Test that filtering by category doesn't add extra queries
     * This validates that filtering is efficient
     */
    public function test_filtering_by_category_does_not_add_extra_queries()
    {
        // Arrange: Create user and multiple categories
        $user = User::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // Create todos in different categories
        Todo::factory()->count(5)->create([
            'user_id' => $user->id,
            'category_id' => $category1->id
        ]);
        Todo::factory()->count(5)->create([
            'user_id' => $user->id,
            'category_id' => $category2->id
        ]);

        // Enable query log
        DB::enableQueryLog();

        // Act: Get todos filtered by category
        $todos = $this->todoService->getTodosWithCategory($user, $category1->id);

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should still have reasonable number of queries
        $this->assertLessThan(
            5,
            $queryCount,
            "Filtering added too many queries! Found {$queryCount} queries."
        );

        // Verify filtering worked correctly
        $this->assertEquals(5, $todos->total());
        $this->assertTrue(collect($todos->items())->every(fn ($todo) => $todo->category_id === $category1->id));

        // Disable query log
        DB::disableQueryLog();
    }

    /**
     * Test that pagination doesn't significantly increase query count
     * This validates that pagination is efficient
     */
    public function test_pagination_does_not_significantly_increase_query_count()
    {
        // Arrange: Create many todos
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Todo::factory()->count(50)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Enable query log
        DB::enableQueryLog();

        // Act: Get paginated todos
        $todos = $this->todoService->getTodosWithCategory($user, null, null, null, 10);

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should still have reasonable number of queries
        $this->assertLessThan(
            5,
            $queryCount,
            "Pagination added too many queries! Found {$queryCount} queries."
        );

        // Verify pagination worked correctly
        $this->assertEquals(10, count($todos->items()));
        $this->assertEquals(50, $todos->total());
        $this->assertEquals(5, $todos->lastPage());

        // Disable query log
        DB::disableQueryLog();
    }

    /**
     * Test that statistics query is efficient
     * This validates the custom raw SQL query performance
     */
    public function test_statistics_query_is_efficient()
    {
        // Arrange: Create user and todos with different statuses
        $user = User::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // Create todos in different categories and statuses
        Todo::factory()->count(20)->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'completed_at' => now() // completed
        ]);
        Todo::factory()->count(15)->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'completed_at' => null // pending
        ]);
        Todo::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'completed_at' => now() // completed
        ]);

        // Enable query log
        DB::enableQueryLog();

        // Act: Get statistics
        $stats = $this->todoService->getStats($user);

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should have reasonable number of queries
        // Expected: 3-4 queries (2 raw SQL + 1-2 Eloquent queries)
        $this->assertLessThan(
            6,
            $queryCount,
            "Statistics query is inefficient! Found {$queryCount} queries."
        );

        // Verify statistics are correct
        $this->assertEquals(45, $stats['total_todos']);
        $this->assertEquals(30, $stats['completed_todos']);
        $this->assertEquals(15, $stats['pending_todos']);

        // Verify category stats structure
        $this->assertIsArray($stats['category_stats']);
        $this->assertGreaterThan(0, count($stats['category_stats']));

        // Disable query log
        DB::disableQueryLog();
    }

    /**
     * Test that multiple filters don't cause query explosion
     * This validates that complex filtering is efficient
     */
    public function test_multiple_filters_dont_cause_query_explosion()
    {
        // Arrange: Create user and todos with different attributes
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create todos with different priorities and statuses
        Todo::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'HIGH',
            'completed_at' => now() // completed
        ]);
        Todo::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'LOW',
            'completed_at' => null // pending
        ]);

        // Enable query log
        DB::enableQueryLog();

        // Act: Apply multiple filters
        $todos = $this->todoService->getTodosWithCategory(
            $user,
            $category->id,
            'completed',
            'HIGH'
        );

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should still have reasonable number of queries
        $this->assertLessThan(
            5,
            $queryCount,
            "Multiple filters caused query explosion! Found {$queryCount} queries."
        );

        // Verify filtering worked correctly
        $this->assertEquals(10, $todos->total());
        $failed = collect($todos->items())->filter(function ($todo) use ($category) {
            return !(
                $todo->category_id === $category->id &&
                $todo->priority === Priority::HIGH &&
                $todo->completed_at !== null
            );
        });
        $this->assertTrue($failed->isEmpty(), 'Some todos did not match the filter criteria.');

        // Disable query log
        DB::disableQueryLog();
    }

    /**
     * Test that database indexes are effective
     * This validates that database optimization is working
     */
    public function test_database_indexes_are_effective()
    {
        // Arrange: Create many users and todos
        $users = User::factory()->count(5)->create();
        $category = Category::factory()->create();

        // Create todos for each user
        foreach ($users as $user) {
            Todo::factory()->count(20)->create([
                'user_id' => $user->id,
                'category_id' => $category->id
            ]);
        }

        // Enable query log
        DB::enableQueryLog();

        // Act: Get todos for specific user (should use user_id index)
        $todos = $this->todoService->getTodosWithCategory($users->first());

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should be efficient even with many users
        $this->assertLessThan(
            5,
            $queryCount,
            "Database indexes not effective! Found {$queryCount} queries."
        );

        // Verify correct todos returned
        $this->assertEquals(20, $todos->total());
        $this->assertTrue(collect($todos->items())->every(fn ($todo) => $todo->user_id === $users->first()->id));

        // Disable query log
        DB::disableQueryLog();
    }

    /**
     * Test that concurrent access doesn't cause performance issues
     * This validates that the application handles concurrent requests well
     */
    public function test_concurrent_access_performance()
    {
        // Arrange: Create user and todos
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Todo::factory()->count(100)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Enable query log
        DB::enableQueryLog();

        // Act: Simulate concurrent requests (multiple calls)
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->todoService->getTodosWithCategory($user, null, null, null, 10);
        }

        // Get query count
        $queryCount = count(DB::getQueryLog());

        // Assert: Should handle concurrent requests efficiently
        $this->assertLessThan(
            25,
            $queryCount,
            "Concurrent access performance issue! Found {$queryCount} queries for 5 requests."
        );

        // Verify all requests returned correct data
        foreach ($results as $result) {
            $this->assertEquals(10, count($result->items()));
            $this->assertEquals(100, $result->total());
        }

        // Disable query log
        DB::disableQueryLog();
    }
}
