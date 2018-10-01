<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'user_id',
        'movie_id',
        'value',
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
