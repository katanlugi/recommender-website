<?php

namespace App\Http\Controllers;

use App\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $movies = Movie::orderBy('release_date', 'desc')
            ->take(9)
            ->get();
        
        $moviesCarousel = Movie::orderBy('release_date', 'desc')
            ->take(3)
            ->get();
        $rates = $this->rates;
        return view('home', compact('movies', 'moviesCarousel', 'rates'));
    }
}
