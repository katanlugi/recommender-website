<?php

namespace App;

use App\CombinedRating;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    // protected $table = 'moviesSmall';

    protected $fillable = [
        'title',
        'adult',
        'budget',
        'homepage',
        'original_language',
        'original_title',
        'overview',
        'poster_path',
        'backdrop_path',
        'release_date',
        'revenue',
        'runtime',
        'tagline'
    ];

    /**
     * Returns the movie title without the year ending enclosed in parenthetis.
     * "test (20) title (2010)" will become "test (20) title"
     *
     * @return string 
     */
    public function getTitle()
    {
        return preg_replace('((\(([0-9]+)\))$)', '', $this->title);
    }

    public function ratings()
    {
        return $this->hasMany('App\Rating', 'movie_id');
    }

    public function combinedRating()
    {
        return $this->hasOne('App\CombinedRating');
    }

    public function imdbID()
    {
        return $this->hasOne('App\Link');
    }

    public function averageRating()
    {
        $avgRating = $this->combinedRating()->first();
        if (!$avgRating)
        {
            Log::info('missing avgRating for movie ID: '.$this->id.' using 0.0 instead');
            return $this->computeAndSaveAverageRating();
        }
        // return $avgRating->getAverageRating();
        return $avgRating->average_rating;
    }

    /**
     * The genres that belong to the movie.
     *
     * @return void
     */
    public function genres()
    {
        return $this->belongsToMany('App\Genre');
    }

    public function getLocalMovieImagePath()
    {
      if (file_exists(public_path('/images/movies/'.$this->id.'.jpg'))) {
        return URL::asset('/images/movies/'.$this->id.'.jpg');
      } else {
        return URL::asset('/images/film-Icon.png');
      }
    }

    public function getLocalBackdropImage()
    {
      if (file_exists(public_path('images/backdrop/'.$this->id.'.jpg'))) {
        return URL::asset('images/backdrop/'.$this->id.'.jpg');
      } else {
        Log::info('backdrop image does NOT exists');  
        return URL::asset('/images/dark-blur-blurred-gradient.png');
      }
    }

    private function computeAndSaveAverageRating()
    {
        $now = now();
        $data = DB::select('SELECT count(*) as number_of_ratings, 
            sum(rating) as rating_sum,
            avg(rating) as average_rating
            FROM ratings WHERE movie_id = ?', [$this->id]
        );
        
        $ar = json_decode(json_encode($data[0]), true);
        
        $ar['movie_id'] = $this->id;
        $ar['created_at'] = $now;
        $ar['updated_at'] = $now;

        DB::table('combined_ratings')->insert($ar);
        return $ar['average_rating'];
    }
}
