<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportBatchedRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ratings;
    // protected $MAX_AUTO_INCREMENT_USER_ID = 1000000;
    public $tries = 1;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $ratings)
    {
        $this->ratings = $ratings;
        // Log::info('Created Job for batch ratings import');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Handling batch ratings import');
        
        DB::connection()->disableQueryLog();
        // // temporarly  allow userID < 1000000
        // DB::unprepared("ALTER TABLE users AUTO_INCREMENT = 0;");
        // Log::info('min auto_increment set to 0');

        $ratingsInserts = array();
        // $usersInserts = array();

        foreach($this->ratings as $rating) {
            $userId     = (int)$rating[0];
            $movieId    = (int)$rating[1];
            $value     = floatval($rating[2]);
            $timestamp  = (int)$rating[3];
            $date = date("Y-m-d H:i:s", $timestamp);

            // // just make sure that the userIds are smaller than our userIds
            // if ($userId >= $this->MAX_AUTO_INCREMENT_USER_ID){
            //     Log::emergency('USER ID LIMIT REACHED!!!');
            //     return;
            // }

            // if (!isset($usersInserts[$userId])) {
            //     $usersInserts[$userId] = array('id' => $userId);
            // }

            array_push($ratingsInserts, [
                'user_id'   => $userId,
                'movie_id'  => $movieId,
                'rating'    => $value,
                'created_at'=> $date
            ]);
        }
        // $ids = array();
        // foreach ($usersInserts as $item) {
        //     array_push($ids, $item['id']);
        // }
        // $keys = ['id'];
        // $val = User::insertIgnore($keys, $ids);

        // unset($usersInserts);
        $val = DB::table('ratings')->insert($ratingsInserts);
        unset($ratingsInserts);

        // // put back limitation for userID > 1000000
        // $maxId = User::find(DB::table('users')->max('id'));
        // DB::unprepared("ALTER TABLE users AUTO_INCREMENT = $maxId;");
        // // DB::unprepared("ALTER TABLE users AUTO_INCREMENT = $this->MAX_AUTO_INCREMENT_USER_ID;");
        DB::connection()->enableQueryLog();
    }
}
