<?php

namespace App\Http\Controllers;

use Laracasts\Flash\Flash;
use Illuminate\Http\Request;
use App\RecommenderPreference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\RecommenderController;

class AdminController extends Controller
{
    private static $recommender = null;

    private function getRecommender()
    {
        if(AdminController::$recommender == null) {
            AdminController::$recommender = new RecommenderController();
        }
        return AdminController::$recommender;
    }
    public function adminSettings()
    {
        $testUrl = env('MAHOUT_SPARK_URL').'/test';
        $recomServerIsRunning = $this->getRecommender()->isRecomServerRunning();
        $prefs = RecommenderController::getRecommenderPreferences();

        return view('adminSettings', compact('recomServerIsRunning','prefs'));
    }

    public function recommenderServerStatus(Request $request)
    {
        $recomServerIsRunning = $this->getRecommender()->isRecomServerRunning();
        return response()->json([
                'status' => 200,
                'serverRunning' => $recomServerIsRunning
        ]);
    }

    public function applyChanges(Request $request)
    {
        Log::info('in applyChanges()');
        $force_train = (isset($request['force_train']) ? true : false);
        $evaluate_model = (isset($request['evaluate_model']) ? true : false);
        $num_iterations = (isset($request['num_iterations']) ? $request['num_iterations'] : '20');
        $num_features = (isset($request['num_features']) ? $request['num_features'] : '20');
        $implicit_pref = (isset($request['implicit_pref']) ? true : false);
        $set_non_negative = (isset($request['set_non_negative']) ? true : false);
        $save = (isset($request['save']) ? 'true' : 'false');

        $val = RecommenderPreference::create([
          'force_train' => $force_train,
          'evaluate_model' => $evaluate_model,
          'implicit_pref' => $implicit_pref,
          'set_non_negative' => $set_non_negative,
          'save' => true,
          'num_iterations' => $num_iterations,
          'num_features' => $num_features
      ]);
        // $val = RecommenderPreference::create([
        //     'force_train' => boolval($force_train),
        //     'evaluate_model' => boolval($evaluate_model),
        //     'implicit_pref' => boolval($implicit_pref),
        //     'set_non_negative' => boolval($set_non_negative),
        //     'save' => boolval($save),
        //     'num_iterations' => $num_iterations,
        //     'num_features' => $num_features
        // ]);
        Log::info('ApplyChange: '.$val);

        return redirect(route('admin-settings'));
        // return response()->json([
        //     'status' => 200,
        //     'message' => 'Recommender settings changed'
        // ]);
    }

    public function resetSettings()
    {
        Log::info('reset settings');
        $force_train = Config::get('recommender.force_train');
        $evaluate_model = Config::get('recommender.evaluate_model');
        $num_iterations = Config::get('recommender.num_iterations');
        $num_features = Config::get('recommender.num_features');
        $implicit_pref = Config::get('recommender.implicit_pref');
        $set_non_negative = Config::get('recommender.set_non_negative');
        $save = Config::get('recommender.save');
        
        $val = RecommenderPreference::create([
            'force_train' => boolval($force_train),
            'evaluate_model' => boolval($evaluate_model),
            'implicit_pref' => boolval($implicit_pref),
            'set_non_negative' => boolval($set_non_negative),
            'save' => boolval($save),
            'num_iterations' => (int)$num_iterations,
            'num_features' => (int)$num_features
        ]);
        Log::info('resetSettings: '.$val);
        return response()->json([
            'status' => 200,
            'message' => 'Recommender settings are back to defaults'
        ]);
    }

    public function startRecomServer()
    {
        $started = $this->getRecommender()->tryLaunchRecommendationServer();
        if ($started) {
          return response(200);
        } else {
          return response(500);
        }
    }

    public function precomputeModel()
    {
        Log::info('Starting load/precompute model');
        if ($this->getRecommender()->precompute()){
            return response()->json([
                'message' => 'Model should be precomputed.',
                'status' => 200
            ]);
        }else{
            return response()->json([
                'message' => 'A problem occured while precomputing the model',
                'status' => 500
            ]);
        }
    }

    public function stopRecomServer()
    {
        // if($this->recommender == null) {
        //     $this->recommender = new RecommenderController();
        // }
        Log::info('in stop recom server');
        if ($this->getRecommender()->tryStopRecommendationServer()) {
            return response()->json([
                'message' => 'Recommendation server stopped.',
                'status' => 200
            ]);
        }else {
            return response()->json([
                'message' => 'Recommendation server could not be stopped.',
                'status' => 500
            ]);
        }
    }
}
