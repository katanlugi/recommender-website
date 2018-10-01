<?php

namespace App\Jobs;

use App\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportRatingsCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    protected $MAX_AUTO_INCREMENT_USER_ID = 1000000;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file)
    {
        $this->file = $file;
        Log::info('Created Job from file: '.$this->file);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Handling Job from file: '.$this->file.' with jobID: '.$this->job->getJobId());
        $t0 = microtime(true);
        $linecount = exec('perl -pe \'s/\r\n|\n|\r/\n/g\' ' . escapeshellarg($this->file) . ' | wc -l');
        Log::info('wc -l linecount is '.$linecount);

        if (($handle = fopen($this->file, 'r')) !== false) {
            DB::connection()->disableQueryLog();
            // extract the first row containing the titles
            $header = fgetcsv($handle);
            $counter = 0;
            $ratingsInserts = array();
            $usersInserts = array();
            $lastAddedUserId = null;
            Log::info('Generating inserts statements...');
            // temporarly  allow userID < 1000000
            if (env('DB_CONNECTION') === 'sqlite') {
              DB::update("UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = 'users'");
            } else {
              DB::unprepared("ALTER TABLE users AUTO_INCREMENT = 0;");
            }
            Log::info('min auto_increment set to 0');

            while(($data = fgetcsv($handle)) != false)
            {
                $userId     = (int)$data[0];
                $movieId    = (int)$data[1];
                $rating     = floatval($data[2]);
                $timestamp  = (int)$data[3];
                $date = date("Y-m-d H:i:s", $timestamp);
                if ($userId >= $this->MAX_AUTO_INCREMENT_USER_ID)
                {
                    Log::emergency('USER ID LIMIT REACHED!!!');
                    return;
                }

                if (!isset($usersInserts[$userId]) && $userId !== $lastAddedUserId)
                {
                    $usersInserts[$userId] = array('id' => $userId);
                }

                array_push($ratingsInserts, [
                    'user_id'   => $userId,
                    'movie_id'  => $movieId,
                    'rating'    => $rating,
                    'created_at'=> $date
                ]);
                
                $counter++;

                if ($counter % 8192 === 0)
                {   
                    $val = DB::table('users')->insert($usersInserts);
                    $lastAddedUserId = $userId;
                    $usersInserts = array();
                    $val = DB::table('ratings')->insert($ratingsInserts);
                    $ratingsInserts = array();
                }
            }
            if (count($ratingsInserts) > 0)
            {
                $val = DB::table('users')->insert($usersInserts);
                $val = DB::table('ratings')->insert($ratingsInserts);
            }
            $t1 = microtime(true);
            Log::info('users and ratings Inserts statements done! time: '.($t1-$t0));
            // put back limitation for userID > 1000000
            if (env('DB_CONNECTION') === 'sqlite') {
              DB::update("UPDATE SQLITE_SEQUENCE SET seq = $this->MAX_AUTO_INCREMENT_USER_ID WHERE name = 'users'");
            } else {
              DB::unprepared("ALTER TABLE users AUTO_INCREMENT = $this->MAX_AUTO_INCREMENT_USER_ID;");
            }
            Log::info('min auto_increment set to back to 1000000');

            unset($usersInserts);
            unset($ratingsInserts);
            $t2 = microtime(true);
            
            DB::connection()->enableQueryLog();
            
            $t3 = microtime(true);
            Log::info('Job done! added '.$counter.' entires. total time: '.($t3-$t0));
            return $counter;
        }
    }
}
