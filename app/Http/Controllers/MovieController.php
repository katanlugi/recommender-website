<?php

namespace App\Http\Controllers;

use App\User;
use App\Genre;
use App\Movie;
use App\Rating;
use App\CombinedRating;
use App\Recommendation;
use Illuminate\Http\Request;
use App\Jobs\RecommendMovies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerateRecommendation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\RecommenderController;

class RatedMovie 
{
    public $rating;
    public $movie;

    public function __construct($rating, $movie)
    {
        $this->rating = $rating;
        $this->movie = $movie;
    }
}

class MovieController extends Controller
{
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index()
    {
        $userId = Auth::id();
        Log::info('userID: '.$userId);
        // $rates = $this->rates;
        $url = '/json/movies/last/9';
        $paginated = false;
        $title = 'Latest Movies';
        return view('movies', compact('title', 'url', 'paginated'));
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function topMovies($howMany = 12)
    {
        $category = "Top";
        $title = "Top";
        $url = '/json/movies/top/6';
        $paginated = false;
        
        return view('movies', compact(['title', 'movies', 'url', 'paginated']));
    }

    public function searchPageEmpty(Request $request)
    {
      $movies = array();
      return view('search', compact('movies'));
    }
    public function searchPage(Request $request, $type, $search)
    {
        $movies = $this->filter($search, $type)
                ->paginate(15);

        return view('search', compact(['movies']));
    }

    public function showCategoryPage(Request $request, $category)
    {
        $url = '/json/category/'.$category;
        $paginated = true;
        $title = $category;
        return view('movies', compact('url', 'paginated', 'title'));
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function myProfile()
    {
        $userId = Auth::id();
        if(!isset($userId)){
            $userId = GuestController::getGuestIdFromSession();
        }
        Log::info('retrieving rated movies for: '.$userId);
        $ratedMovies = User::where('id', $userId)->firstOrFail()->ratedMovies();
        
        $category = 'personal';
        $title = 'Mahouts recommends these movies for you...';
        $paginated = true;
        $url = '/json/generate-recommendations';
        return view('myProfile', compact('userId', 'category', 'ratedMovies', 'title', 'paginated', 'url'));
    }

    /**
    * Display the specified resource.
    *
    * @param  \App\Movie  $movie
    * @return \Illuminate\Http\Response
    */
    public function show(Movie $movie)
    {
        if (!file_exists(public_path('images/backdrop/'.$movie->id.'.jpg'))){
            Log::info('no backdrop -> consolidating');
            $pc = new PosterController();
            $pc->getFromMovieId($movie->id);
        }
        $userId = Auth::id();
        if(!isset($userId)){
            $userId = GuestController::getGuestIdFromSession();
        }
        $movie = $this->retrieveUsersRating($movie, $userId);
        return view('movie', compact('movie'));
    }

    public function filter(String $searchString, String $type)
    {
        $movies = Movie::select('id', 'title');
        switch ($type) {
            case 'contains':
                $movies->where('title', 'LIKE', '%'.$searchString.'%');
                break;
            case 'startsWith':
                $movies->where('title', 'LIKE', $searchString.'%');
                break;
            case 'endsWith':
                $movies->where('title', 'LIKE', '%'.$searchString);
                break;
            case 'exact':
                $movies->where('title', '=', $searchString);
                break;
            default:
                $movies->where('title', 'LIKE', '%'.$searchString.'%');
                break;
        }
        return $movies;
    }

    public function getTopMovies(Request $request, int $howMany = 12)
    {
        $uid = Auth::id();
        Log::info('User id: '.$uid);
        Log::info($request);

        $cbs = CombinedRating::where('number_of_ratings', '>', 10)
                    ->orderBy('average_rating', 'desc')
                    ->take($howMany)
                    ->with('movie')
                    ->get();
        $movies = array();
        foreach($cbs as $cb) {
            array_push($movies, $cb->movie);
        }
        $pc = new PosterController();
        foreach($movies as $m) {
            $pc->getFromMovieId($m->id);
            // $pc->get($m);
        }
        // Parse the movies and for each retrives the rating of the current user.
        $this->retrieveUsersRatings($movies);
        
        return $movies;
    }
    public function getLastMovies(Request $request, int $howMany = 12)
    {   
        $movies = Movie::orderBy('release_date', 'desc')
            ->with('genres')
            ->take($howMany)
            ->get();
        
        $pc = new PosterController();
        foreach($movies as $m) {
            $pc->getFromMovieId($m->id);
            // $pc->get($m);
        }
        // Parse the movies and for each retrives the rating of the current user.
        Log::info('Retrieve users ratings...');
        $this->retrieveUsersRatings($movies);
        Log::info($movies);
        return $movies;
    }

    public function getRatedMovies(Request $request)
    {
        $userId = Auth::id();
        if(!isset($userId)){
            $userId = GuestController::getGuestIdFromSession();
        }
        $ratedMovies = User::where('id', $userId)->firstOrFail()->ratedMovies();
        
        return $ratedMovies;
        
    }

    /**
     * Search the movie table for a title containing the search term
     *
     * @deprecated Should use customSearch or customSearchLimit instead
     * @param Request $request
     * @return void
     */
    public function search(Request $request)
    {
      $movies = Movie::select('id', 'title')
                      ->where('title', 'LIKE', '%'.$request->search.'%')
                      ->take(10)
                      ->get();
      return $movies;
    }

    /**
     * Search the movie table for the given searchString with a limit of 12 results max.
     * The type can be used to narrow the search down by using either 'startsWith', 
     * 'endsWith', 'contains' or 'exact'.
     *
     * @param Request $request
     * @param [type] $type
     * @param [type] $searchString
     * @return void
     */
    public function customSearch(Request $request, $type, $searchString)
    {
        return $this->customSearchLimit($request, $type, $searchString, 12);
    }

    /**
     * Search the movie table for the given searchString with a variable limit used
     * to determine the maximum number of results to return (use 0 for unlimited).
     * The type can be used to narrow the search down by using either 
     * 'startsWith', 'endsWith', 'contains' or 'exact'.
     *
     * @param Request $request
     * @param [type] $type
     * @param [type] $searchString
     * @param [type] $howMany
     * @return void
     */
    public function customSearchLimit(Request $request, $type, $searchString, $howMany)
    {
        $movies = $this->filter($request->search, $type);
        if ($howMany == 0) {
            return $movies->get();
        } else {
            return $movies->take($howMany)->get();
        }
    }

    public function getCategory(Request $request, $category)
    {
        $movies = Genre::where('name', $category)
            ->first()
            ->movies()
            ->orderBy('release_date', 'desc')
            ->paginate(9);
        $this->retrieveUsersRatings($movies);
        return $movies;
    }

    public function generateRecommendations(Request $request)
    {
      Log::info('---> generateRecommendations()');
      $userId = Auth::id();
      if(!isset($userId)){
        $userId = GuestController::getGuestIdFromSession();
      }
      $movies = Redis::get($userId);
      Log::info($movies);
      
      if (empty($movies)) {
        $this->recommendMovies(true);
        // $nbJobs = DB::table('jobs')->count();
        $queue = null;
        $nbJobs = \Queue::size();
        $numJobs = Redis::connection()->llen('queues:default');
        Log::info($numJobs);

        // Here we select details for up to 1000 jobs
        $jobs = Redis::connection()->lrange('queues:default', 0, 1000);

        return response()->json([
          'status' => 'pending',
          'nbJobs' => $nbJobs,
          'jobs' => $jobs
        ]);
      } else {
        Log::info('already in redis...');
        return response()->json([
          'status' => 'done'
        ]);
      }
    }

    public function hasRecommendations(Request $request)
    {
      $userId = Auth::id();
      if(!isset($userId)){
        $userId = GuestController::getGuestIdFromSession();
      }
      $movies = Redis::get($userId);
      
      if (!empty($movies)) {
        return response()->json([
          'status' => 'ok',
          'movies' => $movies
        ]);
        // return response('ok', 200);
      } else {
        $queue = null;
        $nbJobs = \Queue::size();
        // Here we select details for up to 1000 jobs
        $jobs = Redis::connection()->lrange('queues:default', 0, 1000);
        Log::info('@@@@@@ returning 204 and nbJobs: '.$nbJobs);
        return response()->json([
          'status' => 'pending',
          'nbJobs' => $nbJobs,
          'jobs' => $jobs
        ]);
        // return response()->json([
        //   'status' => 'pending',
        //   'nbJobs' => $nbJobs,
        //   'jobs' => $jobs
        // ], 204);
        // return response('not ok', 204);
      }
    }

    public function getRecommendations(Request $request)
    {
      Log::info('---> getRecommendations()');
      $userId = Auth::id();
      if(!isset($userId)){
        $userId = GuestController::getGuestIdFromSession();
      }
      $suggestedMovies = json_decode(Redis::get($userId), true);
      $ids = array();
      $recommendedMovies = array();
      
      $pc = new PosterController();
      Log::info('[suggestedMovies]:');
      Log::info($suggestedMovies);
      foreach($suggestedMovies as $movie) {
        $movieId = $pc->getFromMovieId($movie['productId']);
        array_push($ids, $movie['productId']);
      }
      $suggestedMovies = $this->retrieveMoviesFromIds($ids);
      // Parsees the movies and for each retrives the rating of the current user.
      // if(isset($suggestedMovies)) $this->retrieveUsersRatings($suggestedMovies);
      $this->retrieveUsersRatings($suggestedMovies);

      return $suggestedMovies;
    }

    public function getRecommendations_OLD(Request $request)
    {
        try {
            $userId = Auth::id();
            if(!isset($userId)){
                $userId = GuestController::getGuestIdFromSession();
            }
            $pc = new PosterController();
            $suggestedMovies = $this->recommendMovies(true);
            
            $ids = array();
            $recommendedMovies = array();
            // if (isset($suggestedMovies)) {
            foreach($suggestedMovies as $movie) {
                $movieId = $pc->getFromMovieId($movie['productId']);
                array_push($ids, $movie['productId']);
            }
            $suggestedMovies = $this->retrieveMoviesFromIds($ids);
            // Parsees the movies and for each retrives the rating of the current user.
            // if(isset($suggestedMovies)) $this->retrieveUsersRatings($suggestedMovies);
            $this->retrieveUsersRatings($suggestedMovies);

            return $suggestedMovies;
        }catch( \Error $e) {
            Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => $e
            ]);
        }catch(\Exception $e){
            Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => $e
            ]);
        }
    }

    private function recommendMovies($isLive = true)
    {
      $movies = [];
        if ($isLive){
          $userId = Auth::id();
          if(!isset($userId)){
              $userId = GuestController::getGuestIdFromSession();
          }
          Log::info('dispatching recommendMovies for userId: '.$userId);
          RecommendMovies::dispatch($userId, 6);
          // $rc = new RecommenderController();
          
          // $movies = $rc->recommend($userId, 6);
        } else {
            /*
             * This is for debug purpose only to avoid having to load the recommender.
             * It will simply return the top 6 movies.
             */
            $movies = $this->getTopMovies(6);
        }

        return $movies;
    }

    private function getRecommendedMovies()
    {
        $recommendations = Auth::user()->recommendations()->get();

        $movies = array();
        foreach ($recommendations as $recom) {
            if (!is_null($recom->movie)) {
                array_push($movies, $recom->movie);
            }
        }
        return $movies;
    }

    private function retrieveMoviesFromIds(array $ids)
    {
       $movies = array();
       foreach ($ids as $id) {
           $movie = Movie::where('id', $id)->first();
           array_push($movies, $movie);
       }
       return $movies;
    }

    private function retrieveUsersRatings($movies)
    {
        $userId = Auth::id();
        if(!isset($userId) || $userId == null){
            $userId = GuestController::getGuestIdFromSession();
        }
        foreach($movies as $movie) {
            $this->retrieveUsersRating($movie, $userId);
        }

        return $movies;
    }

    private function retrieveUsersRating(Movie $movie, int $userId)
    {
        $rating = Rating::where('user_id', $userId)
                    ->where('movie_id', $movie->id)
                    ->select('rating')
                    ->get()
                    ->first();
        $movie['rating'] = $rating['rating'];
        return $movie;
    }
}
