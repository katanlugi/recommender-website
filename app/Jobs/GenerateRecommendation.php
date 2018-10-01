<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateRecommendation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $userId;
    public $tries = 1;
    // public $timeout = 180;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->userId = $user->id;
        Log::info('Created Job from userId: '.$this->userId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(User $user)
    {
        Log::info('Handling Job from user: '.$this->user->id.' with jobID: '.$this->job->getJobId());
        $userId = $this->user->id;
        $output = shell_exec('java -jar '.env('MAHOUT_JAR_FILE_PATH').' '.$userId);
        Log::info('Job from user: '.$userId.' done! '.$output);
    }
}
