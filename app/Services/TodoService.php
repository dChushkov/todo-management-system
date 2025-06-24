<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TodoService
{
    /**
     * Get todo statistics with custom raw SQL query
     * Demonstrates performance optimization with raw SQL
     */
    public function getStats(User $user): array
    {
        // Custom raw SQL query for performance demonstration
        $categoryStats = DB::select("
            SELECT 
                c.name as category_name,
                COUNT(t.id) as total_todos,
                COUNT(CASE WHEN t.completed_at IS NOT NULL THEN 1 END) as completed_todos,
                COUNT(CASE WHEN t.completed_at IS NULL THEN 1 END) as pending_todos
            FROM categories c
            LEFT JOIN todos t ON c.id = t.category_id AND t.user_id = ?
            GROUP BY c.id, c.name
            ORDER BY total_todos DESC
        ", [$user->id]);

        // Priority distribution
        $priorityStats = DB::select("
            SELECT 
                priority,
                COUNT(*) as count
            FROM todos 
            WHERE user_id = ?
            GROUP BY priority
        ", [$user->id]);

        return [
            'category_stats' => $categoryStats,
            'priority_stats' => $priorityStats,
            'total_todos' => $user->todos()->count(),
            'completed_todos' => $user->todos()->completed()->count(),
            'pending_todos' => $user->todos()->incomplete()->count(),
        ];
    }

    /**
     * Get todos with eager loading to prevent N+1 problem
     */
    public function getTodosWithCategory(User $user, ?int $categoryId = null, ?string $status = null, ?string $priority = null, int $perPage = 5): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $user->todos()->with('category');

        if ($categoryId) {
            $query->byCategory($categoryId);
        }
        if ($status === 'completed') {
            $query->completed();
        } elseif ($status === 'pending') {
            $query->incomplete();
        }
        if ($priority) {
            $query->byPriority($priority);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new todo
     */
    public function createTodo(User $user, array $data): Todo
    {
        return $user->todos()->create($data);
    }

    /**
     * Update a todo
     */
    public function updateTodo(Todo $todo, array $data): bool
    {
        return $todo->update($data);
    }

    /**
     * Delete a todo
     */
    public function deleteTodo(Todo $todo): bool
    {
        return $todo->delete();
    }

    /**
     * Mark todo as completed
     */
    public function markAsCompleted(Todo $todo): void
    {
        $todo->markAsCompleted();
    }

    /**
     * Mark todo as incomplete
     */
    public function markAsIncomplete(Todo $todo): void
    {
        $todo->markAsIncomplete();
    }
} 