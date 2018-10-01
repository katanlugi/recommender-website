<?php

namespace App\Http\Controllers;

use App\Movie;
use App\Rating;
use carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PagesController extends Controller
{
    public function about()
    {
       return view('about');
    }

    public function settings()
    {
        $nbRatedMovies = Rating::where('user_id', Auth::id())->count();
       
        return view('settings', compact('nbRatedMovies'));
    }
}
