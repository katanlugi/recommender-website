<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Jobs\ImportBatchedRatings;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PrepareImportRatingsJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    public $tries = 1;
    /**
     * The number of seconds the job can run before timing out.
     * On slow internet connection can take > 30min
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Handling PrepareImportRatingsJobs with: '.$this->file);
        Log::info('Handling creation of importRatingsJobs with jobID: '.$this->job->getJobId());
        $linecount = exec('perl -pe \'s/\r\n|\n|\r/\n/g\' ' . escapeshellarg($this->file) . ' | wc -l');
        Log::info('The file has '.$linecount.' lines.');

        // Log::info('TODO currently skipping...');
        if(($handle = fopen($this->file, 'r')) !== false) {
            // extract the first row containing the titles
            $header = fgetcsv($handle);

            $batchRatings = array();
            $counter = 0;
            Log::info("Dispatching importRatingsJobs...");
            while(($data = fgetcsv($handle)) != false){
                array_push($batchRatings, $data);
                $counter++;
                if (env('DB_CONNECTION') === 'sqlite') {
                  if ($counter % 8 === 0){
                    ImportBatchedRatings::dispatch($batchRatings);
                    // reset the array for the next ratings
                    $batchRatings = array();
                  }
                } else {
                  if ($counter % 8192 === 0){
                  // if ($counter % 65536 === 0){
                      ImportBatchedRatings::dispatch($batchRatings);
                      // reset the array for the next ratings
                      $batchRatings = array();
                  }
                }
            }
            if(count($batchRatings)>0){
                ImportBatchedRatings::dispatch($batchRatings);
            }
            Log::info("Dispatched all importRatingsJobs.");
        }
    }
}
