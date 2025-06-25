<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Todo Management System')</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-900">
                        Todo Manager
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    @if (request()->is('todos'))
                        <span class="text-gray-700 font-semibold" id="user-name"></span>
                        <button onclick="logout()" class="text-red-600 hover:text-red-800">Logout</button>
                    @elseif (request()->is('login'))
                        <a href="/register" class="text-blue-600 hover:text-blue-800">Register</a>
                    @elseif (request()->is('register'))
                        <a href="/login" class="text-blue-600 hover:text-blue-800">Login</a>
                    @else
                        <a href="/login" class="text-blue-600 hover:text-blue-800">Sign In</a>
                        <a href="/register" class="text-gray-700 hover:text-blue-600">Register</a>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <!-- Toast Notifications -->
    <div id="toast" class="fixed top-4 right-4 z-50 hidden">
        <div class="bg-white border-l-4 border-green-500 shadow-lg rounded-lg p-4 max-w-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <!-- Success Icon -->
                    <svg id="success-icon" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <!-- Error Icon -->
                    <svg id="error-icon" class="h-5 w-5 text-red-400 hidden" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900" id="toast-message"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center h-full">
            <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // CSRF Token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // API base URL
        const API_BASE = '/api';
        
        // Utility functions
        function showToast(message, type = 'success') {
            // Suppress technical errors and 404s
            if (
                message.includes('No query results') ||
                message.includes('404') ||
                message.includes('Not Found')
            ) {
                return;
            }
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastBox = toast.querySelector('div.bg-white');
            const successIcon = document.getElementById('success-icon');
            const errorIcon = document.getElementById('error-icon');
            
            // Set color and icon
            if (type === 'error') {
                toastBox.classList.remove('border-green-500');
                toastBox.classList.add('border-red-500');
                successIcon.classList.add('hidden');
                errorIcon.classList.remove('hidden');
            } else {
                toastBox.classList.remove('border-red-500');
                toastBox.classList.add('border-green-500');
                successIcon.classList.remove('hidden');
                errorIcon.classList.add('hidden');
            }
            toastMessage.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
        
        function showLoading() {
            document.getElementById('loading').classList.remove('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loading').classList.add('hidden');
        }
        
        // API helper functions
        async function apiRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            };
            
            const finalOptions = { ...defaultOptions, ...options };
            
            try {
                const response = await fetch(url, finalOptions);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'An error occurred');
                }
                
                return data;
            } catch (error) {
                console.error('API Error:', error);
                showToast(error.message, 'error');
                throw error;
            }
        }
        
        // Authentication functions
        async function checkAuth() {
            try {
                const response = await apiRequest(`${API_BASE}/user`);
                // document.getElementById('user-nav').style.display = 'flex';
                document.getElementById('user-name').textContent = response.user.name;
                return true;
            } catch (error) {
                return false;
            }
        }
        
        async function logout() {
            try {
                await apiRequest(`${API_BASE}/logout`, { method: 'POST' });
                window.location.href = '/login';
            } catch (error) {
                console.error('Logout error:', error);
            }
        }
        
        // Initialize auth check and CSRF cookie
        document.addEventListener('DOMContentLoaded', async function() {
            await fetch('/csrf-token', { credentials: 'same-origin' });
            const protectedPages = ['/todos', '/profile'];
            if (protectedPages.includes(window.location.pathname)) {
                checkAuth();
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html> 