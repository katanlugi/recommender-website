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

class ImportNewUsersFromCSVFile implements ShouldQueue
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
    public function __construct($what)
    {
        $this->file = $what;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('1 handling ImportNewUsersFromCSVFile with: '.$this->file);
        if (($handle = fopen($this->file, 'r')) !== false) {
            $header = fgetcsv($handle); // extract the header with the titles
            $counter = 0;
            $lastAddedUserId = null;
            $userIds = array();
            
            // DB::connection()->disableQueryLog();
            // temporarly  allow userID < 1000000
            if (env('DB_CONNECTION') === 'sqlite') {
              DB::update("UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = 'users'");
            } else {
              DB::unprepared("ALTER TABLE users AUTO_INCREMENT = 0;");
            }
            Log::info('min auto_increment set to 0');

            // retirev all userIds from the csv file and uniquly add them the $userIds.
            while(($data = fgetcsv($handle)) !== false) {
                $id = (int)$data[0];
                // just make sure that the userIds are smaller than our userIds
                if ($id >= $this->MAX_AUTO_INCREMENT_USER_ID){
                    Log::emergency('USER ID LIMIT REACHED!!!');
                    return;
                }
                if (!isset($userIds[$id]) && $id !== $lastAddedUserId) {
                    $userIds[$id] = array('id' => $id);
                }
                if (env('DB_CONNECTION') === 'sqlite') {
                  $val = DB::table('users')->insert($userIds);
                  $lastAddedUserId = $id;
                  $userIds = array();
                } else {
                  if (count($userIds) % 8192 === 0){
                      $val = DB::table('users')->insert($userIds);
                      $lastAddedUserId = $id;
                      $userIds = array();
                  }
                }
            }
            if (count($userIds) > 0) {
                $val = DB::table('users')->insert($userIds);
            }
            unset($userIds);
            // put back limitation for userID > 1000000
            // Product::with( 'urls','prices')->where('user_id', '=', $id)->min('cost')->get();
            $max = User::with('id')->max('id');
            $maxId = max($max, $this->MAX_AUTO_INCREMENT_USER_ID);
            if (env('DB_CONNECTION') === 'sqlite') {
              DB::update("UPDATE SQLITE_SEQUENCE SET seq = $maxId WHERE name = 'users'");
            } else {
              DB::unprepared("ALTER TABLE users AUTO_INCREMENT = $maxId;");
            }
            Log::info("min auto_increment set to back to $maxId");
            // DB::connection()->enableQueryLog();
            Log::info('All userIds added to Database');
        }
    }
}
