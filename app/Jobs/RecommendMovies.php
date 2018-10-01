<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Controllers\RecommenderController;

class RecommendMovies implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $userId;
  protected $howMany;

  public $tries = 1;
  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($userId, int $howMany)
  {
    $this->userId = $userId;
    $this->howMany = $howMany;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    // only compute if we don't already have recommendations for this userId.
    $movies = Redis::get($this->userId); 
    if (empty($movies)) {
      $rc = new RecommenderController();
      $movies = $rc->recommend($this->userId, $this->howMany);
      Redis::set($this->userId, json_encode($movies));
    }
  }
}
