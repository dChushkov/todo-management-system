<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TodoStoreRequest;
use App\Models\Category;
use App\Models\Todo;
use App\Services\TodoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TodoController extends Controller
{
    public function __construct(
        private TodoService $todoService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $categoryId = $request->get('category');
        $status = $request->get('status');
        $priority = $request->get('priority');
        $perPage = (int) $request->get('per_page', 5);
        $todos = $this->todoService->getTodosWithCategory($user, $categoryId, $status, $priority, $perPage);
        return response()->json([
            'todos' => collect($todos->items())->map(function ($todo) {
                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'description' => $todo->description,
                    'priority' => is_object($todo->priority) ? $todo->priority->value : $todo->priority,
                    'priority_label' => is_object($todo->priority) ? $todo->priority->label() : (string) $todo->priority,
                    'priority_color' => is_object($todo->priority) && method_exists($todo->priority, 'color') ? $todo->priority->color() : null,
                    'completed_at' => $todo->completed_at,
                    'is_completed' => method_exists($todo, 'isCompleted') ? $todo->isCompleted() : (bool) $todo->completed_at,
                    'category' => [
                        'id' => $todo->category->id ?? null,
                        'name' => $todo->category->name ?? null,
                    ],
                    'created_at' => $todo->created_at,
                    'updated_at' => $todo->updated_at,
                ];
            }),
            'pagination' => [
                'current_page' => $todos->currentPage(),
                'last_page' => $todos->lastPage(),
                'per_page' => $todos->perPage(),
                'total' => $todos->total(),
            ]
        ]);
    }

    public function store(TodoStoreRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $todo = $this->todoService->createTodo($user, $data);
        return response()->json([
            'message' => 'Todo created successfully',
            'todo' => [
                'id' => $todo->id,
                'title' => $todo->title,
                'description' => $todo->description,
                'priority' => $todo->priority->value,
                'priority_label' => $todo->priority->label(),
                'priority_color' => $todo->priority->color(),
                'category' => [
                    'id' => $todo->category->id,
                    'name' => $todo->category->name,
                ],
                'created_at' => $todo->created_at,
            ]
        ], 201);
    }

    public function show(Todo $todo): JsonResponse
    {
        if ($todo->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json([
            'todo' => [
                'id' => $todo->id,
                'title' => $todo->title,
                'description' => $todo->description,
                'priority' => $todo->priority->value,
                'priority_label' => $todo->priority->label(),
                'priority_color' => $todo->priority->color(),
                'completed_at' => $todo->completed_at,
                'is_completed' => $todo->isCompleted(),
                'category' => [
                    'id' => $todo->category->id,
                    'name' => $todo->category->name,
                ],
                'created_at' => $todo->created_at,
                'updated_at' => $todo->updated_at,
            ]
        ]);
    }

    public function update(TodoStoreRequest $request, Todo $todo): JsonResponse
    {
        if ($todo->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $data = $request->validated();
        $this->todoService->updateTodo($todo, $data);
        return response()->json([
            'message' => 'Todo updated successfully',
            'todo' => [
                'id' => $todo->id,
                'title' => $todo->title,
                'description' => $todo->description,
                'priority' => $todo->priority->value,
                'priority_label' => $todo->priority->label(),
                'priority_color' => $todo->priority->color(),
                'category' => [
                    'id' => $todo->category->id,
                    'name' => $todo->category->name,
                ],
                'updated_at' => $todo->updated_at,
            ]
        ]);
    }

    public function destroy(Todo $todo): JsonResponse
    {
        if ($todo->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $this->todoService->deleteTodo($todo);
        return response()->json([
            'message' => 'Todo deleted successfully'
        ]);
    }

    public function complete(Todo $todo): JsonResponse
    {
        if ($todo->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $this->todoService->markAsCompleted($todo);
        return response()->json([
            'message' => 'Todo marked as completed',
            'completed_at' => $todo->completed_at
        ]);
    }

    public function incomplete(Todo $todo): JsonResponse
    {
        if ($todo->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $this->todoService->markAsIncomplete($todo);
        return response()->json([
            'message' => 'Todo marked as incomplete',
            'completed_at' => null
        ]);
    }

    public function stats(): JsonResponse
    {
        $user = Auth::user();
        try {
            $data = DB::select("
                SELECT category_id, COUNT(*) AS total
                FROM todos
                WHERE user_id = ?
                GROUP BY category_id
            ", [$user->id]);
            $total = DB::table('todos')->where('user_id', $user->id)->count();
            $completed = DB::table('todos')->where('user_id', $user->id)->whereNotNull('completed_at')->count();
            $pending = DB::table('todos')->where('user_id', $user->id)->whereNull('completed_at')->count();
        } catch (\Throwable $e) {
            $data = [];
            $total = 0;
            $completed = 0;
            $pending = 0;
        }
        return response()->json([
            'category_stats' => $data,
            'total_todos' => $total,
            'completed_todos' => $completed,
            'pending_todos' => $pending,
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();
        return response()->json([
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            })
        ]);
    }
}
