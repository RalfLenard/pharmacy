<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{




    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return Inertia::render('Dashboard');
        } else {
            return Inertia::render('Guest');
        }
    }


}
