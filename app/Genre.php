<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $fillable = [
        'name',
        'created_at',
        'updated_at'
    ];

    /**
     * The movies that belong to the genre
     *
     * @return void
     */
    public function movies()
    {
        return $this->belongsToMany('App\Movie');
    }
}
