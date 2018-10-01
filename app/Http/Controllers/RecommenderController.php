<?php

namespace App\Http\Controllers;

use App\Rating;
use Laracasts\Flash\Flash;
use Illuminate\Http\Request;
use App\RecommenderPreference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class RecommenderController extends Controller
{
    public static function getRecommenderPreferences()
    {
        $prefs = array();
        if(RecommenderPreference::count() > 0){
            $recomPref = RecommenderPreference::latest()->first();
            
            $prefs['force_train'] = $recomPref->force_train;
            $prefs['evaluate_model'] = $recomPref->evaluate_model;
            $prefs['num_iterations'] = $recomPref->num_iterations;
            $prefs['num_features'] = $recomPref->num_features;
            $prefs['implicit_pref'] = $recomPref->implicit_pref;
            $prefs['set_non_negative'] = $recomPref->set_non_negative;
            $prefs['save'] = $recomPref->save;
        }else{
            $prefs['force_train'] = Config::get('recommender.force_train');
            $prefs['evaluate_model'] = Config::get('recommender.evaluate_model');
            $prefs['num_iterations'] = Config::get('recommender.num_iterations');
            $prefs['num_features'] = Config::get('recommender.num_features');
            $prefs['implicit_pref'] = Config::get('recommender.implicit_pref');
            $prefs['set_non_negative'] = Config::get('recommender.set_non_negative');
            $prefs['save'] = Config::get('recommender.save');
        }
        return $prefs;
    }

    /**
     * Retrieves the desired n recommendations for the given userId.
     * Depending on the env variable, it will use the local .jar 
     * recommender or the query the url set in .env
     *
     * @param int $userId   the userId for which we want the recommendations
     * @param int $howMany  how many recommendation should we retrieve
     * @return void
     */
    public function recommend(int $userId, int $howMany = 6)
    {
        if($this->isRecomServerRunning() == false) {
            Log::error('server not running');
            $this->tryLaunchRecommendationServer();
        }else{
            Log::info('server is already running');
        }
        $url = env('MAHOUT_SPARK');
        Log::info("recommending $howMany movies...");
        $anon_recoms = $this->anonymousRecommendation($userId, $howMany);
        if(isset($anon_recoms)) $anon_recoms = array_reverse($anon_recoms);
        // Log::info("===== Anonymous recommendations ======");
        // Log::info($anon_recoms);
        // Log::info("============================");
        return isset($anon_recoms) ? $anon_recoms : array();
        
        // $matrix_recoms = $this->recommendations($userId, $howMany);
        // Log::info("===== Precomputed recommendations ======");
        // Log::info($matrix_recoms);
        // Log::info("============================");
        // return isset($matrix_recoms) ? $matrix_recoms : array();
    }

    public function isRecomServerRunning()
    {
        $testUrl = env('MAHOUT_SPARK_URL').'/test';
        try {
            // $json = file_get_contents($testUrl);
            $resp = $this->callURL($testUrl);
            Log::info("resp: $resp");
            if($resp == null || $resp == ''){
                return false;
            }else{
                return true;
            }
            // return $this->callURL($testUrl);
        }catch(\Exception $e) {
            Log::error('Could not start the recommendation server.');
            Log::error($e);
            Flash::error('Could not start the recommendation server.');
            return false;
        }
        return true;
    }

    public function tryStopRecommendationServer()
    {
        Log::info('Try stopping recommendation server.');
        $url = env('MAHOUT_SPARK_URL').'/quit';
        try {
            $json = $this->callURL($url);
            Log::info('JSON: '.$json);
            Log::info('The recommendation server was successfuly stopped.');
            return true;
        } catch(\Exception $e) {
            Log::info('Problem occured while trying to stop the recommendation server.');
            Log::info($e);
            return false;
        }
    }

    public function tryLaunchRecommendationServer()
    {
        Log::info('Launching recommendation server');
        $prefs = RecommenderController::getRecommenderPreferences();
        
        $command = "java -jar ".env('MAHOUT_JAR_FILE_PATH')
            .' -dbs '.env('MAHOUT_DB_SERVER')
            .' -dbn '.env('MAHOUT_DB_NAME')
            .' -dbu '.env('MAHOUT_DB_USER')
            .' -dbp '.env('MAHOUT_DB_PASSWORD')
            .' -ft ' .$prefs['force_train']     // Force Train
            .' -e '  .$prefs['evaluate_model']	// evaluate the trained model
            .' -m '  .$prefs['num_iterations']	// numIterations [default: 5]
            .' -nf ' .$prefs['num_features']    // numFeatures [default: 10]
            .' -im ' .$prefs['implicit_pref']	// implicitPrefs [default: false]
            .' -nn ' .$prefs['set_non_negative']// setNonNegative (best when inverse of implicitPrefs)
            .' -s '  .$prefs['save']
            //.' -v'
            .">/dev/null 2>&1 &"; // Open in a new process and continue executiong of php
        Log::info('Starting recommender with command: '.$command);
        // Flash::success('Recommender Started with: '.$command);
        $val = exec($command);
        Log::info('RecomServer should be launched');
        // return $this->isRecomServerRunning();
        return true;
    }

    public function precompute()
    {
        /**
         * Ask for recommendations for userId 1. This will 
         * precompute the model if it's not already done.
         */
        $url = env('MAHOUT_SPARK_URL').'/recommendations/1/2';
        try {
            $resp = $this->callURL($url);
            Log::info($resp);
            return true;
        }catch(\Exception $e) {
            Log::error('Could not reach the recommendation server.');
            Flash::error('Could not reach the recommendation server.');
            return false;
        }    
    }
    
    public function precompute_old()
    {
        $url = env('MAHOUT_SPARK_URL').'/precomputeItemSimilarities';
        try {
            $json = $this->callURL($url);
            return true;
        }catch(\Exception $e) {
            Log::error('Could not reach the recommendation server.');
            Flash::error('Could not reach the recommendation server.');
            return false;
        }    
    }

    private function getRecommendationsFromServer(String $url)
    {
        $json_data = array();
        
        try {
            // $json = file_get_contents($url);
            $json = $this->callURL($url);

            $json_data = json_decode($json, true);
        }catch (\Exception $e) {
            Log::error('Could not reach the recommendation server.');
            Flash::error('Could not reach the recommendation server.');
            $this->tryLaunchRecommendationServer();
            // \Session::flash('status', 'Could not contact the recommendation server.');
        }
        return $json_data;
    }

    // private function callURL(string $url, int $timeout = 10)
    private function callURL(string $url)
    {
        // $curlSession = curl_init();
        // curl_setopt($curlSession, CURLOPT_URL, $url);
        // curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
        // curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    
        // $jsonData = json_decode(curl_exec($curlSession));
        // curl_close($curlSession);
        // Log::info('CALL_URL.data: '.$jsonData);
        // return $jsonData;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_PROXY, $proxy); // $proxy is ip of proxy server
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        // curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $httpCode = curl_getinfo($ch , CURLINFO_HTTP_CODE); // this results 0 every time
        $response = curl_exec($ch);
        if ($response === false) {
            $val = curl_error($ch);
            Log::info(stripslashes($val));
        }
        curl_close($ch);
        return $response;

        // $curl = curl_init();
        // curl_setopt($curl, CURLOPT_URL, $url);
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_HEADER, false);
        // $data = curl_exec($curl);
        // curl_close($curl);
        // Log::info('CALL_URL.data: '.$data);
        // return $data;
    }

    private function recommendations(int $userId, int $howMany)
    {
        $url = env('MAHOUT_SPARK_URL').'/recommendations/'.$userId.'/'.$howMany;
        Log::info('url: '.$url);
        return $this->getRecommendationsFromServer($url);
    }

    private function anonymousRecommendation(int $userId, int $howMany)
    {
        $ratings = Rating::where('user_id', $userId)->get();
        $prefs = $this->preparePrefString($ratings);
        $url = env('MAHOUT_SPARK_URL').'/anonymousRecommender/'.$userId.'/'.$prefs.'/'.$howMany;
        // http://localhost:4567/anonymousRecommender/1000000/1:5.0,2:3.5,158:4.5,169:3.0,362:4.0,364:4.0,96030:0.5/5
        // http://localhost:4567/anonymousRecommender/1:4.5,2:2.5,10:3.5,48:1.5,130490:3.5/6
        // dd($url);
        Log::info('url: '.$url);
        return $this->getRecommendationsFromServer($url);
    }

    private function preparePrefString($ratings)
    {
        $prefs = "";
        foreach ($ratings as $rating) {
            if ($prefs == "") {
                $prefs .= $rating['movie_id'].':'.$rating['rating'];
            } else {
                $prefs .= ','.$rating['movie_id'].':'.$rating['rating'];
            }
        }
        return $prefs;
    }
    private function shouldPerformAnonymousRecommendation()
    {
        return true;
    }
}
