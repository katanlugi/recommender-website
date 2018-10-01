<?php

namespace App\Http\Controllers;

use App\Rating;
use Carbon\Carbon;
use App\CombinedRating;
use App\Recommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Requests\RatingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\GuestController;

class RatingController extends Controller
{
  
  public function resetRatings()
  {
    $user = Auth::user();
    $movieIds = Rating::select('movie_id')->where('user_id', $user->id)->get();
    $val = DB::table('ratings')->where('user_id', $user->id)->delete();
    Redis::del($user->id);
    // Make sure to recompute the average ratings for this movie
    $this->computeAvgRatingForMovieIds($movieIds);

    return $val;
  }
  
  public function addRating()
  {
    $ratingValue = number_format(request('rating'), 1, '.', ',');
    $movieId = request('movie_id');
    
    $userId = Auth::id();
    if(!isset($userId)){
      $userId = GuestController::getGuestIdFromSession();
    }
    Redis::del($userId);
    
    Log::info("inserting new rating... user_id:$userId movie_id:$movieId rating:$ratingValue");
    $entry = Rating::where(['user_id'=>$userId, 'movie_id'=>$movieId])->first();
    if($entry){
      $this->removeRatingFromCombinedRatings($movieId, $ratingValue);
      DB::delete("DELETE FROM ratings 
          WHERE (user_id = $userId AND movie_id = $movieId)");
    }
    $now = Carbon::now();

    DB::table('ratings')->insert([
      'user_id' => $userId,
      'movie_id' => $movieId,
      'rating' => $ratingValue,
      'created_at' => $now,
      'updated_at' => $now
    ]);
    
    $this->addRatingToCombinedRatings($movieId, $ratingValue);

    return response()->json([
      'data' => [
          'movieId' => $movieId,
          'rating' => $ratingValue,
      ],
      'status' => 200
    ]);
  }
  /**
   * Remove the specified resource from storage.
   *
   * @param  \App\Rating  $rating
   * @return \Illuminate\Http\Response
   */
  public function destroy(Request $req, $movieId)
  {
      if(Auth::check()){
          $userId = Auth::id();    
      }else{
          $userId = GuestController::getGuestIdFromSession();
      }
      Redis::del($userId);
      $this->removeRating($movieId, $userId);
      if ($req->method == "GET"){
          return redirect(URL::previous());
      }else{
          return response()->json([
              'data' => [
                  'movieId' => $movieId,
                  'rating' => 'reseted'
              ],
              'status' => 200
          ]);
      }
      
  }

  private function removeRating($movieId, $userId)
  {
      $entry = Rating::where(['user_id'=>$userId, 'movie_id'=>$movieId])->first();
      if(!$entry){
          return;
      }
      $this->removeRatingFromCombinedRatings($movieId, $entry->rating);
      DB::delete("DELETE FROM ratings 
          WHERE (user_id = $userId AND movie_id = $movieId)");
  }

  private function removeRatingFromCombinedRatings($movieId, $rating)
  {
      $cr = CombinedRating::where('movie_id', $movieId)->first();
      if ($cr) {
          $cr->rating_sum -= $rating;
          $cr->number_of_ratings -= 1;
          if ($cr->number_of_ratings != 0) {
            $cr->average_rating = $cr->rating_sum / $cr->number_of_ratings;
          } else {
            $cr->average_rating = null;
          }
          
          $cr->save();
      } else {
          $this->computeAvgRatingForMovieId($movieId);
      }
  }

  private function addRatingToCombinedRatings($movieId, $rating)
  {
      $cr = CombinedRating::where('movie_id', $movieId)->first();
      if ($cr) {
          $cr->rating_sum += $rating;
          $cr->number_of_ratings += 1;
          $cr->average_rating = $cr->rating_sum / $cr->number_of_ratings;
          $cr->save();
      } else {
          $this->computeAvgRatingForMovieId($movieId);
      }
  }

  private function computeAvgRatingForMovieIds(array $ids)
  {
      foreach ($ids as $id) {
          $this->computeAvgRatingForMovieId($id);
      }
  }

  private function computeAvgRatingForMovieId(int $movieId)
  {
      $data = DB::select('SELECT count(*) as number_of_ratings, 
          sum(rating) as rating_sum,
          avg(rating) as average_rating
          FROM ratings WHERE movie_id = ?', [$movieId]
      );
      $now = now();
      $ar = json_decode(json_encode($data[0]), true);
      $ar['movie_id'] = $movieId;
      $ar['created_at'] = $now;
      $ar['updated_at'] = $now;
      DB::table('combined_ratings')->insert($ar);
  }

  public function getRating()
  {
      # code...
      return response()->json([
          'data' => 'Todo',
          'status' => 500
      ]);
  }

  /**
   * Undocumented function
   * @deprecated This function should not be used anymore as it relies on outdated, inefficient code.
   * @return void
   */
  private function generateRecommendations()
  {
      return;
      $user = Auth::user();
      if (true) {
          // We first delete all previous recommendations
          DB::table('recommendations')->where('user_id', $user->id)->delete();
          $command = 'java -jar '
                      .env('MAHOUT_JAR_FILE_PATH')
                      .' -id '.$user->id
                      .' -dbs '.env('MAHOUT_DB_SERVER')
                      .' -dbn '.env('MAHOUT_DB_NAME')
                      .' -dbu '.env('MAHOUT_DB_USER')
                      .' -dbp '.env('MAHOUT_DB_PASSWORD')
                      .' -s true';
          Log::info('Starting recommender with command: '.$command);
          $output = shell_exec($command);
          // $output = shell_exec('java -jar '
          //     .env('MAHOUT_JAR_FILE_PATH')
          //     .' -id '.$user->id
          //     .' -dbs '.env('MAHOUT_DB_SERVER')
          //     .' -dbn '.env('MAHOUT_DB_NAME')
          //     .' -dbu '.env('MAHOUT_DB_USER')
          //     .' -dbp '.env('MAHOUT_DB_PASSWORD')
          //     .' -s true'
          // );
          Log::info('Generated recommendations:');
          Log::info($output);
          return response()->json([
              'message'   => 'Recommendations for '.$user->id.' generated...',
              'data'      => $output,
              'status'    => 200
          ]);
      } else {
          // User the Queues to handle the recommentations...
          GenerateRecommendation::dispatch($user);
          Log::info('Dispatched request from user: '.$user->id);
          return response()->json([
              'data' => 'Processing...',
              'status' => 200
          ]);
      }
  }
}
