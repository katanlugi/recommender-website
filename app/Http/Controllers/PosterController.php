<?php

namespace App\Http\Controllers;

use App\Link;
use App\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MovieDataController;

class PosterController extends Controller
{
    /**
     * Retrieve data for the given movieId. This will retrieve the movie from the database and update its fields.
     * We only accept a movieId and not a movie object itself as the passed object might have been modified
     * outside and thus when performing the model update, those modifications might have side effects.
     *
     * @param int $movieId
     * @return void
     */
    public function getFromMovieId(int $movieId)
    {
        $movie = Movie::where('id', $movieId)->first();
        $this->get($movie);
    }

    /**
     * Retrieve data for the given Movie object. It will either retrieve them using the 
     * MovieController, the posterImageRetriever or fallback on the fallback image.
     * Should not get accessed directly from outside as the movie
     *
     * @param Movie $movie
     * @return void
     */
    private function get(Movie $movie)
    {
      $imagePath = public_path('images/movies/'.$movie->id.'.jpg');
      $backdropPath = public_path('images/backdrop/'.$movie->id.'.jpg');

      if (!file_exists($imagePath))
      {
        $imageUrl = "https://image.tmdb.org/t/p/w500";

        // If we don't have a poster_path for this movie yet, try to consolidate it first
        if (!isset($movie->poster_path) || $movie->poser_path === '') {
          $mc = new MovieDataController();
          $mc->consolidateMovie($movie);
        }
        // try to retrieve the image from TheMovieDB if we can't use the fallback image.
        $imageRetrieved = false;
        if (isset($movie->poster_path) && $movie->poster_path !== '') {
          $imageUrl .= $movie->poster_path;
          $imageRetrieved = $this->retrievePosterImage($imageUrl, $imagePath);
        }
      }
      if (!file_exists($backdropPath)) {
        $backdropUrl = "https://image.tmdb.org/t/p/original";
        if (isset($movie->backdrop_path) && $movie->backdrop_path !== '') {
          $backdropUrl .= $movie->backdrop_path;
          $imageRetrieved = $this->retrievePosterImage($backdropUrl, $backdropPath);
        } else {
          Log::info('DB missing backdrop_path for movie_id: '.$movie->id);
        }
      }
    }
    
    public function get_old(int $id)
    {
        $imagePath = public_path('images/movies/'.$id.'.jpg');
        
        if (!file_exists($imagePath))
        {
            $imdb_id = $this->localToImdbID($id);

            $data = $this->retrieveData($imdb_id);
            $m = $data['movie_results'][0];
            $poster = $m['poster_path'];
            $imageUrl = "https://image.tmdb.org/t/p/w500$poster";
            
            $this->retrievePosterImage($imageUrl, $imagePath);
        }

        return $imagePath;
    }

    private function localToImdbID($id)
    {
        return 'tt'.Link::select('imdb_id')->where('movie_id', $id)->first()->imdb_id;
    }

    private function retrievePosterImage($url, $imagePath)
    {
      try {
        $this->createMissingFolders();
        $ch = curl_init($url);
        $fp = fopen($imagePath, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return true;
      }catch(\Exception $e){
        Log::error($e);
        return false;
      }
    }

    private function createMissingFolders()
    {
      // first make sure that the movie folder exists
      if(!file_exists(public_path('images/movies'))) {
        mkdir(public_path('images/movies'), 0755, true);
      }
      // first make sure that the backdrop folder exists
      if(!file_exists(public_path('images/backdrop'))) {
        mkdir(public_path('images/backdrop'), 0755, true);
      }
    }

    private function retrieveData($id)
    {
        $key = env('THEMOVIEDB_API_KEY');
        $url = 'https://api.themoviedb.org/3/find/';
        $url .= $id.'?api_key='.$key.'&external_source=imdb_id';

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
          ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        return json_decode($response, true);
    }
}
