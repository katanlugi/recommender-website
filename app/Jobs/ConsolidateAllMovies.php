<?php

namespace App\Jobs;

use App\Link;
use App\Movie;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ConsolidateAllMovies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 3600;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        Log::info('Created Job for consolidating all movies.');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Started Job for consolidating all movies...');
        $t0 = microtime(true);

        // this date is when the backdrop_path was added to the db
        $date = Carbon::create(2017, 12, 19, 0, 0, 0, 'Europe/Zurich');
        $movies = Movie::whereDate('updated_at', '<', $date)
                    //->orWhere('updated_at', '<=', Carbon::today()->toDateString())
                    ->chunk(20, function($movies) {
                        $this->consolidateAllMovies($movies);
                    });
        $t1 = microtime(true);
        Log::info('All movies consolidation time: '.($t1 - $t1));
    }

    private function consolidateAllMovies($movies)
    {
        Log::info('Consolidating batch of '.count($movies).' movies.');
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

    private function consolidateMovie(Movie $movie)
    {
        $imdb_id = $this->localToImdbID($movie->id);
        // Log::info('Consolidating movieID: '.$movie->id.'  imdb: '.$imdb_id);
        $data = $this->retrieveData($imdb_id);
        if (str_contains($data['header']['http_code'], '404')) {
            $this->missingMovie($movie, $imdb_id);
        } else if (str_contains($data['header']['http_code'], '200')){
            // Log::info('retrieved movie data from themvdb...');
            $this->addMovieDataToDB($movie, $data);
        } else {
            Log::error('ERROR STATUS CODE NOT HANDLED!!!! '.$data['header']['http_code']);
        }
        return $data['header'];
    }

    private function addMovieDataToDB(Movie $movie, array $data)
    {
        if (!isset($data['body'])) {
            Log::error('addMovieDataToDB $data[body] is not set!');
        }
        $updateArray = array();
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'adult');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'budget');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'homepage');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'original_language');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'original_title');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'overview');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'poster_path');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'backdrop_path');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'release_date');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'revenue');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'runtime');
        $updateArray = $this->addToUpdateArrayIfExists($data, $updateArray, 'tagline');
        $movie->update($updateArray);
    }

    private function addToUpdateArrayIfExists(array $data, array $a, String $str)
    {
        $tmp = $data['body'][$str];
        // Only add the value if it is set and it is not null or empty.
        if (isset($tmp) && $tmp != null && $tmp != '') {
            $a[$str] = $tmp;
        }
        return $a;
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
        return 'tt'.Link::select('imdb_id')->where('movie_id', $id)->first()->imdb_id;
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
