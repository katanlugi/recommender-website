<?php

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\GuestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', 'MovieController@index');

Route::get('test', function(){
    return view('test');
});

Route::get('aboutPage', 'PagesController@about')->name('about');
// Route::get('category/{category}', 'MovieController@category')->name('category');
Route::get('category/{category}', 'MovieController@showCategoryPage')->name('category');
Route::get('search/{search}', 'MovieController@searchPageEmpty');
Route::post('search/{search}', 'MovieController@search');
Route::post('search/{type}/{search}', 'MovieController@customSearch');
Route::get('search/{type}/{search}', 'MovieController@searchPage');
Route::post('search/{type}/{search}/{howMany}', 'MovieController@customSearchLimit');
Route::get('poster/{id}', 'PosterController@getFromMovieId')->name('poster');
Route::get('movies', 'MovieController@index')->name('allMovies');
Route::get('movies/top', 'MovieController@topMovies')->name('topMovies');
Route::get('movies/{movie}', 'MovieController@show')->name('showMovie');

Route::get('myprofile', 'MovieController@myProfile')->name('myProfile');

Route::post('ratings', 'RatingController@addRating')->name('addRating');
Route::get('ratings', 'RatingController@getRating');
Route::get('ratings/delete/{rating}', 'RatingController@destroy')->name('removeRating');
Route::post('ratings/delete/{rating}', 'RatingController@destroy');

Route::get('guest/reset', 'GuestController@resetGuestId');
Route::post('guest/reset', 'GuestController@resetGuestId')->name('resetSession');
Route::get('guest/get', 'GuestController@getGuestIdFromSession');
Route::get('guest/session', 'GuestController@getSessionId');
Route::get('guest/restore-session/{session}', 'GuestController@restoreSession');

// =======
// Routes used by fetch requests
Route::get('json/movies/rated', 'MovieController@getRatedMovies')->name('getRatedMovies');
Route::get('json/movies/top/', 'MovieController@getTopMovies');
Route::get('json/movies/top/{howMany}', 'MovieController@getTopMovies');
Route::get('json/movies/last/{howMany}', 'MovieController@getLastMovies');
Route::get('json/category/{category}', 'MovieController@getCategory');
// Route::get('json/generate-recommendations', 'MovieController@getRecommendations')->name('getRecom');
Route::get('json/generate-recommendations', 'MovieController@generateRecommendations');
Route::get('json/has-recommendations', 'MovieController@hasRecommendations');
Route::get('json/get-recommendations', 'MovieController@getRecommendations');

Route::get('json/userid', function(Request $request) {
  $userId = Auth::id();
  if(!isset($userId)){
    $userId = GuestController::getGuestIdFromSession();
  }
  Log::info('returning userId: '.$userId);
  return $userId;
});
// ======

Route::group(['middleware' => ['auth']], function() {

    Route::post('resetRatings', 'RatingController@resetRatings')->name('resetRatings');
    
    Route::get('downloads', 'DownloadController@index')->name('downloads');
    Route::get('downloadDataSet', 'DownloadController@downloadDataset')->name('dl-dataset');
    Route::get('downloadMovies', 'DownloadController@downloadMovies')->name('dl-movies');

    Route::get('settings', 'PagesController@settings')->name('settings');

    Route::group(['middleware' => [
        \App\Http\Middleware\CheckAdmin::class
    ]], function() {
        Route::get('update-dataset', 'DownloadController@updateDataset')->name('update-dataset');
        Route::get('update-movies', 'DownloadController@updateMovies')->name('update-movies');
        Route::get('import-data', 'DataImporterController@index')->name('import-data');
        Route::post('import-movie-data', 'DataImporterController@importMovies')->name('import-movie');
        Route::post('import-link-data', 'DataImporterController@importLinks')->name('import-links');
        Route::post('import-ratings-data', 'DataImporterController@importRatings')->name('import-ratings');
        Route::post('computeAvgRatings', 'DataImporterController@computeAvgRatings')->name('computeAvgRatings');
        Route::post('consolidate-db', 'MovieDataController@consolidateDB')->name('consolidate-db');
        
        Route::get('adminSettings', 'AdminController@adminSettings')->name('admin-settings');
        Route::post('apply-changes', 'AdminController@applyChanges')->name('apply-changes');
        Route::post('reset-settings', 'AdminController@resetSettings')->name('reset-settings');
        Route::post('recomServerStatus', 'AdminController@recommenderServerStatus')->name('recom-server-status');
        Route::post('startRecomServer', 'AdminController@startRecomServer')->name('start-recom-server');
        Route::post('stopRecomServer', 'AdminController@stopRecomServer')->name('stop-recom-server');
        Route::post('precompute', 'AdminController@precomputeModel')->name('precompute');
    });
});

