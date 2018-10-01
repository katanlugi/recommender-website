<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    // protected $table = 'ratingsSmall';
    
    protected $fillable = [
        'user_id',
        'movie_id',
        'rating',
        'created_at',
        'updated_at'
    ];

    public function movie()
    {
        return $this->hasOne('App\Movie', 'id', 'movie_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'id', 'user_id');
    }
}
