<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function extendFillableId()
    {
        array_push($this->fillable, 'id');
    }

    public function ratings()
    {
        return $this->hasMany('App\Rating', 'user_id');
    }

    public function recommendations()
    {
        return $this->hasMany('App\Recommendation', 'user_id');
    }

    public function ratedMovies()
    {
        $paginator = $this->ratings()->select(['movie_id', 'rating'])->with('movie')->paginate(6);
        $paginator->getCollection()->transform(function($item) {
            $movie = $item->movie;
            $movie['rating'] = $item->rating;
            return $movie;
        });
        return $paginator;
    }
}
