<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CombinedRating extends Model
{
    protected $fillable = [
        'movie_id',
        'rating_sum',
        'number_of_ratings',
        'average_rating'
    ];

    public function movie()
    {
        return $this->belongsTo('App\Movie');
    }

    /**
     * Computes the average rating by deviding the sum by the number of ratings.
     *
     * @return decimal average rating
     */
    public function getAverageRating()
    {
        return $this->rating_sum / $this->number_of_ratings;
    }
}
