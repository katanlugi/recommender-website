<?php

namespace App\Http\Controllers;

use App\Link;
use App\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\ConsolidateAllMovies;
use Illuminate\Support\Facades\Log;

class MovieDataController extends Controller
{
    public function consolidateDB()
    {
        Log::info('in consolidateDB...');
        
        if (true) {
            Log::info('Dispatching into Jobs Queue');
            ConsolidateAllMovies::dispatch();
            return response()->json([
                'data'  => 'Processing...',
                'status'=> 200
            ]);
        } else {
            // This is kept just in case, the current best way to proceed is by using the job queue
            // as called in the "if(true)" just above.
            $movies = Movie::orderBy('id')
                                ->whereNull('updated_at')
                                // ->where('id', '<', 1000)
                                ->chunk(10, function($movies) {
                                    $this->consolidateAllMovies($movies);
                                });
        }
       
    }

    private function consolidateAllMovies($movies)
    {
        foreach($movies as $m) {
            $header = $this->consolidateMovie($m);
            $remaining = $header['X-RateLimit-Remaining'];
            // Log::info('allowed remaining: '.$remaining);
            if ($remaining <= 1){
                $reset = (int)$header['X-RateLimit-Reset'];
                $delta = $reset - time();
                Log::info('Should wait for reset...sleeping for a bit: '.$delta);
                usleep($delta);
            }
        }
    }

    public function consolidateMovie(Movie $movie)
    {
        $imdb_id = $this->localToImdbID($movie->id);
        if ($imdb_id == null) {
            return;
        }
        // Log::info('Consolidating movieID: '.$movie->id.'  imdb: '.$imdb_id);
        $data = $this->retrieveData($imdb_id);
        if (str_contains($data['header']['http_code'], '404')) {
            $this->missingMovie($movie, $imdb_id);
        } else if (str_contains($data['header']['http_code'], '200')){
            $this->addMovieDataToDB($movie, $data);
        } else {
            Log::error('ERROR STATUS CODE NOT HANDLED!!!! '.$data['header']['http_code']);
        }
        return $data['header'];
    }

    private function addToArrayIfExistsAndNotNUllOrEmpty(array $arr, array $data, string $what)
    {
        if (!isset($data['body'][$what])){
            return $arr;
        }
        $val = $data['body'][$what];
        if ($val == null || $val == '') {
            return $arr;
        }
        
        $arr[$what] = $val;
        return $arr;
    }

    private function buildUpdateArray(array $data)
    {
        $arr = array();
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'adult');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'budget');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'homepage');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'original_language');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'original_title');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'overview');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'poster_path');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'backdrop_path');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'release_date');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'revenue');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'runtime');
        $arr = $this->addToArrayIfExistsAndNotNullOrEmpty($arr, $data, 'tagline');

        return $arr;
    }
    private function addMovieDataToDB(Movie $movie, array $data)
    {
        $updateArray = $this->buildUpdateArray($data);
        // if(isset($movie['rating'])){
        //     unset($movie['rating']);
        // }
        $movie->update($updateArray);
    }
    private function missingMovie(Movie $movie, string $imdb_id)
    {
        Log::info('Could not find movie '.$movie->id.' with imdbID:'. $imdb_id);
        $val = DB::table('movies_not_found')->where('movie_id', $movie->id)->get();
        if (count($val) === 0) {
            $now = now();
            $val = DB::table('movies_not_found')->insert([
                'movie_id' => $movie->id,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }

    private function localToImdbID($id)
    {
        $link = Link::select('imdb_id')->where('movie_id', $id)->first();
        if ($link){
            return 'tt'.$link->imdb_id;
        }else{
            return null;
        }
        // return 'tt'.Link::select('imdb_id')->where('movie_id', $id)->firstOrFail()->imdb_id;
    }

    private function retrieveData(string $imdb_id)
    {
        $key = env('THEMOVIEDB_API_KEY');
        $url = 'https://api.themoviedb.org/3/movie/';
        $url .= $imdb_id.'?api_key='.$key.'&external_source=imdb_id';

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HEADER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
          ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header_text = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($curl);
        $headers = array();
        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                if (! empty(trim($line))) {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$key] = $value;
                }
            }
        }
        $rsp = [
            'header' => $headers,
            'body'   => json_decode($body, true)   
        ];
        return $rsp;
    }

    private function retrievePosterImage($url, $imagePath)
    {
        $ch = curl_init($url);
        $fp = fopen($imagePath, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }
}
