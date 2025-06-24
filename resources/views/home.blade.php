@extends('layouts.app')

@section('title', 'Welcome - Todo Management System')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh]">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome to Todo Manager</h1>
    <p class="text-lg text-gray-600 mb-8">Organize your tasks, boost your productivity, and never miss a deadline.</p>
    <div class="flex space-x-4">
        <a href="/login" class="px-6 py-3 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 transition">Sign In</a>
        <a href="/register" class="px-6 py-3 bg-gray-200 text-gray-900 rounded-md font-semibold hover:bg-gray-300 transition">Register</a>
    </div>
</div>
@endsection 