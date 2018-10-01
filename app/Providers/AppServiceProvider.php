<?php

namespace App\Providers;

use App\Genre;
use App\Movie;
use App\Rating;
use Laravel\Horizon\Horizon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        if (Schema::hasTable('genres'))
        {
            $genres = Genre::latest()->get();
            View::share('genres', $genres);
        }
        if (Schema::hasTable('movies'))
        {
            $nbMovies = Movie::count();
            View::share('nbMovies', $nbMovies);
        }
        if (Schema::hasTable('ratings'))
        {
            $nbRatings = Rating::count();
            View::share('nbRatings', $nbRatings);
        }
       
        /**
        * Log jobs
        *
        * Job processed
        */
        // Queue::after(function ( JobProcessed $event ) {
        //   Log::info('############## QUEUE AFTER ##############');
        //   Log::notice('Job done: ' . $event->job->resolveName());
        //   Log::info('Job done: ' . $event->job->resolveName());
        //   Log::info('############## QUEUE AFTER ##############');
        // });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // if ($this->app->environment('production')) {
        //     $this->app->register(\Jenssegers\Rollbar\RollbarServiceProvider::class);
        // }

        Horizon::auth(function ($request) {
            // return true;
            $user = $request->user();
            if (!isset($user) || $user == null){
                return false;
            }
            $admins = explode(",", env('ADMIN_USER'));
            return in_array($user->email, $admins);
        });
    }
}
