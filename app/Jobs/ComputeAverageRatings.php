<?php

namespace App\Jobs;

use App\Movie;
use App\Rating;
use App\CombinedRating;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ComputeAverageRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    /**
     * The number of seconds the job can run before timing out.
     * On slow internet connection can take > 30min
     *
     * @var int
     */
    public $timeout = 900; // 900 => 15 min

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        Log::info('Created Job for precomputing average ratings for all movies.');
        $this->combinedRatings = array();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('precomputing average ratings...');
        $this->t0 = microtime(true);

        $nbMovies = Movie::count();
        $t0 = microtime(true);

        // Remove all previous precomputed ratings
        Log::info('remove all previous CombinedRatings (truncate)');
        DB::table('combined_ratings')->truncate();

        $ids = Movie::select('id')->where('id', '>', 0)->chunk(100, function($ids){
            $averages = array();
            foreach ($ids as $id) {
                $id = $id['id'];
                // $val = CombinedRating::where('movie_id', $id)->count();
                // if ($val > 0) {
                //     // we already have a CombinedRating for this movie -> skip
                //     continue;
                // }
                $now = now();
                
                $data = DB::select('SELECT count(*) as number_of_ratings, 
                    sum(rating) as rating_sum,
                    avg(rating) as average_rating
                    FROM ratings WHERE movie_id = ?', [$id]
                );
                $ar = json_decode(json_encode($data[0]), true);
                $ar['movie_id'] = $id;
                $ar['created_at'] = $now;
                $ar['updated_at'] = $now;
                
                array_push($averages, $ar);   
            }
            DB::table('combined_ratings')->insert($averages);
        });
        $t4 = microtime(true);
        Log::info('t4-0: '.($t4 - $t0));
    }
}
