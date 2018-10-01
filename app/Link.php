<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $fillable = [
        'movie_id',
        'imdb_id',
        'tmdb_id'
    ];

    public function movie()
    {
        return $this->belongsTo('App\Movie');
    }
}
