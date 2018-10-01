<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecommenderPreference extends Model
{
    protected $fillable = [
        'force_train',
        'evaluate_model',
        'num_iterations',
        'num_features',
        'implicit_pref',
        'set_non_negative',
        'save'
    ];
}
