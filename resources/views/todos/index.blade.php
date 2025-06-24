@extends('layouts.app')

@section('title', 'Todos - Todo Management System')

@section('content')
<div x-data="todoApp()" x-init="init()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">My Todos</h1>
            <button @click="showCreateModal = true" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Todo
            </button>
        </div>
        <!-- Stats -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4" x-show="stats">
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm font-medium text-gray-500">Total</div>
                <div class="text-2xl font-bold text-gray-900" x-text="stats.total_todos"></div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm font-medium text-gray-500">Completed</div>
                <div class="text-2xl font-bold text-green-600" x-text="stats.completed_todos"></div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm font-medium text-gray-500">Pending</div>
                <div class="text-2xl font-bold text-yellow-600" x-text="stats.pending_todos"></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select x-model="selectedCategory" @change="loadTodos()" 
                        class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Categories</option>
                    <template x-for="category in categories" :key="category.id">
                        <option :value="category.id" x-text="category.name"></option>
                    </template>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select x-model="selectedStatus" @change="loadTodos()" 
                        class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select x-model="selectedPriority" @change="loadTodos()" 
                        class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Priorities</option>
                    <option value="HIGH">High</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="LOW">Low</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Pagination Controls -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <label class="text-sm font-medium text-gray-700 mr-2">Per page:</label>
            <select x-model="perPage" @change="changePerPage()" class="border border-gray-300 rounded-md px-2 py-1">
                <option value="5">5</option>
                <option value="10">10</option>
            </select>
        </div>
        <div class="space-x-2">
            <button @click="prevPage()" :disabled="pagination.current_page === 1" class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50">Previous</button>
            <span class="text-sm">Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span></span>
            <button @click="nextPage()" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50">Next</button>
        </div>
    </div>

    <!-- Todos List -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div x-show="loading" class="text-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Loading todos...</p>
            </div>
            
            <div x-show="!loading && todos.length === 0" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No todos</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new todo.</p>
            </div>
            
            <div x-show="!loading && todos.length > 0" class="space-y-4">
                <template x-for="todo in todos" :key="todo.id">
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow min-h-[120px] flex flex-col justify-between">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3 flex-1">
                                <input type="checkbox" 
                                       :checked="todo.is_completed"
                                       @change="toggleTodo(todo)"
                                       class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-lg font-medium text-gray-900" 
                                            :class="{ 'line-through text-gray-500': todo.is_completed }"
                                            x-text="todo.title"></h3>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                              :class="{
                                                  'bg-red-100 text-red-800': todo.priority === 'HIGH',
                                                  'bg-yellow-100 text-yellow-800': todo.priority === 'MEDIUM',
                                                  'bg-green-100 text-green-800': todo.priority === 'LOW'
                                              }"
                                              x-text="todo.priority_label"></span>
                                    </div>
                                    
                                    <p x-show="todo.description" class="mt-1 text-sm text-gray-600" x-text="todo.description.length > 50 ? todo.description.slice(0, 50) + '...' : todo.description"></p>
                                    
                                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded" x-text="todo.category.name"></span>
                                        <span x-text="new Date(todo.created_at).toLocaleDateString()"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <button @click="viewTodo(todo)" class="text-gray-600 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button @click="editTodo(todo)" 
                                        class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button @click="deleteTodo(todo)" 
                                        class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal || showEditModal" 
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" x-text="showEditModal ? 'Edit Todo' : 'Create Todo'"></h3>
                
                <form @submit.prevent="showEditModal ? updateTodo() : createTodo()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" x-model="form.title" required
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea x-model="form.description" rows="3"
                                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select x-model="form.category_id" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Category</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <select x-model="form.priority" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Priority</option>
                                <option value="LOW">Low</option>
                                <option value="MEDIUM">Medium</option>
                                <option value="HIGH">High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="closeModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                            <span x-text="showEditModal ? 'Update' : 'Create'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div x-show="showViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">View Todo</h3>
                <div class="space-y-2">
                    <div><span class="font-semibold">Title:</span> <span x-text="viewingTodo?.title"></span></div>
                    <div><span class="font-semibold">Description:</span> <span x-text="viewingTodo?.description"></span></div>
                    <div><span class="font-semibold">Category:</span> <span x-text="viewingTodo?.category?.name"></span></div>
                    <div><span class="font-semibold">Priority:</span> <span x-text="viewingTodo?.priority_label"></span></div>
                    <div><span class="font-semibold">Status:</span> <span x-text="viewingTodo?.is_completed ? 'Completed' : 'Pending'"></span></div>
                    <div><span class="font-semibold">Created at:</span> <span x-text="new Date(viewingTodo?.created_at).toLocaleString()"></span></div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="closeViewModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function todoApp() {
    return {
        todos: [],
        categories: [],
        stats: {
            category_stats: [],
            priority_stats: [],
            total_todos: 0,
            completed_todos: 0,
            pending_todos: 0,
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 5,
            total: 0,
        },
        perPage: 5,
        loading: false,
        showCreateModal: false,
        showEditModal: false,
        showViewModal: false,
        viewingTodo: null,
        selectedCategory: '',
        selectedStatus: '',
        selectedPriority: '',
        editingTodo: null,
        form: {
            title: '',
            description: '',
            category_id: '',
            priority: ''
        },
        
        async init() {
            await this.loadCategories();
            await this.loadTodos();
            await this.loadStats();
        },
        
        async loadCategories() {
            try {
                const response = await apiRequest(`${API_BASE}/categories`);
                this.categories = response.categories;
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },
        
        async loadTodos(page = null) {
            this.loading = true;
            try {
                let url = `${API_BASE}/todos`;
                const params = new URLSearchParams();
                
                if (this.selectedCategory) params.append('category', this.selectedCategory);
                if (this.selectedStatus) params.append('status', this.selectedStatus);
                if (this.selectedPriority) params.append('priority', this.selectedPriority);
                params.append('per_page', this.perPage);
                params.append('page', page || this.pagination.current_page);
                
                if (params.toString()) {
                    url += '?' + params.toString();
                }
                
                const response = await apiRequest(url);
                this.todos = response.todos;
                this.pagination = response.pagination;
            } catch (error) {
                console.error('Failed to load todos:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async loadStats() {
            try {
                const response = await apiRequest(`${API_BASE}/todos/stats`);
                this.stats = response;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },
        
        async createTodo() {
            try {
                await apiRequest(`${API_BASE}/todos`, {
                    method: 'POST',
                    body: JSON.stringify(this.form)
                });
                
                showToast('Todo created successfully!');
                this.closeModal();
                await this.loadTodos();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to create todo:', error);
            }
        },
        
        async updateTodo() {
            try {
                await apiRequest(`${API_BASE}/todos/${this.editingTodo.id}`, {
                    method: 'PUT',
                    body: JSON.stringify(this.form)
                });
                
                showToast('Todo updated successfully!');
                this.closeModal();
                await this.loadTodos();
            } catch (error) {
                console.error('Failed to update todo:', error);
            }
        },
        
        async deleteTodo(todo) {
            if (!confirm('Are you sure you want to delete this todo?')) return;
            
            try {
                await apiRequest(`${API_BASE}/todos/${todo.id}`, {
                    method: 'DELETE'
                });
                
                showToast('Todo deleted successfully!');
                await this.loadTodos();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to delete todo:', error);
            }
        },
        
        async toggleTodo(todo) {
            try {
                const url = todo.is_completed 
                    ? `${API_BASE}/todos/${todo.id}/incomplete`
                    : `${API_BASE}/todos/${todo.id}/complete`;
                
                await apiRequest(url, { method: 'PATCH' });
                
                showToast(todo.is_completed ? 'Todo marked as incomplete' : 'Todo marked as completed');
                await this.loadTodos();
                await this.loadStats();
            } catch (error) {
                console.error('Failed to toggle todo:', error);
            }
        },
        
        editTodo(todo) {
            this.editingTodo = todo;
            this.form = {
                title: todo.title,
                description: todo.description || '',
                category_id: todo.category.id,
                priority: todo.priority
            };
            this.showEditModal = true;
        },
        
        closeModal() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.editingTodo = null;
            this.form = {
                title: '',
                description: '',
                category_id: '',
                priority: ''
            };
        },
        
        viewTodo(todo) {
            this.viewingTodo = todo;
            this.showViewModal = true;
        },
        
        closeViewModal() {
            this.showViewModal = false;
            this.viewingTodo = null;
        },
        
        changePerPage() {
            this.pagination.current_page = 1;
            this.loadTodos(1);
        },
        
        prevPage() {
            if (this.pagination.current_page > 1) {
                this.loadTodos(this.pagination.current_page - 1);
            }
        },
        
        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.loadTodos(this.pagination.current_page + 1);
            }
        }
    }
}
</script>
@endpush
@endsection 