<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Show login page
     */
    public function login(): \Illuminate\View\View
    {
        return view('auth.login');
    }

    /**
     * Show register page
     */
    public function register(): \Illuminate\View\View
    {
        return view('auth.register');
    }

    /**
     * Show todos page
     */
    public function todos(): \Illuminate\View\View
    {
        return view('todos.index');
    }

    /**
     * Show home page
     */
    public function home(): \Illuminate\View\View
    {
        return view('home');
    }
} 