<?php

namespace App\Http\Controllers;

use App\Genre;
use App\Movie;
use App\Rating;
use App\CombinedRating;
use Illuminate\Http\Request;
use App\Jobs\ImportRatingsCSV;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ComputeAverageRatings;
use App\Jobs\ProcessRatingsCSVFile;
use App\Jobs\PrepareImportRatingsJobs;
use App\Jobs\ImportNewUsersFromCSVFile;

class DataImporterController extends Controller
{
    public function __construct()
    {
        # code...
    }

    public function index()
    {
        $data = array();
        $nbUnconsolidatedMovies = count(Movie::whereNull('updated_at')->get());
        $genre = Genre::where('name', '(no genres listed)')->first();
        $nbMissingCategory = -1;
        if ($genre) {
            $noGenreId = $genre->id;
            $nbMissingCategory = DB::table('genre_movie')->where('genre_id', $noGenreId)->count();
        }
        
        $nbMissingAvgRating = (Movie::count() - CombinedRating::count());
        
        return view('dataImporter', compact(
            'data',
            'nbUnconsolidatedMovies',
            'nbMissingAvgRating',
            'nbMissingCategory'
        ));
    }

    public function importMovies(Request $request)
    {
        $file = $this->tryRetrieveFile();
        if (!$file) {
            return response()->json([
                'message' => 'still uploading...',
                'status' => 200
            ]);
        }
        
        $val = $this->parseMovieCSV($file);
        Log::info('Import movies DONE!');
        return response()->json([
            'message' => $val.' ratings added.',
            'data' => json_encode($val),
            'status' => 200
        ]);
    }

    public function importLinks(Request $request)
    {
        Log::info('in importLinks');
        $file = $this->tryRetrieveFile();
        if (!$file) {
            return response()->json([
                'message' => 'still uploading...',
                'status' => 200
            ]);
        }

        $val = $this->parseLinksCSV($file);
        return response()->json([
            'message'   => $val.' links added.',
            'data'      => json_encode($val),
            'status'    => 200
        ]);
    }

    public function importRatings(Request $request)
    {
        $file = $this->tryRetrieveFile();
        if (!$file) {
            return response()->json([
                'message' => 'still uploading...',
                'status' => 200
            ]);
        }
        
        ImportNewUsersFromCSVFile::withChain([
            new PrepareImportRatingsJobs($file)
        ])->dispatch($file);

        // ProcessRatingsCSVFile::withChain([
        //     new ImportNewUsersFromCSVFile($file),
        //     new PrepareImportRatingsJobs($file)
        // ])->dispatch($file);

        // PrepareImportRatingsJobs::dispatch($file);
        // $val = $this->parseRatingsCSV($file);
        
        return response()->json([
            'data' => 'Processing import ratings...',
            'status' => 200
        ]);
    }

    public function computeAvgRatings(Request $request)
    {
        ComputeAverageRatings::dispatch();
        Log::info('Dispatched ComputeAverageRatings...');
        return response()->json([
            'data' => 'Processing...',
            'status' => 200
        ]);
    }

    private function tryRetrieveFile()
    {
        $target_path = storage_path('app/public/');
        //$nbLines = $request['nbLines'];
        $tmp_name = $_FILES['upload']['tmp_name'];
        $filename = $_FILES['upload']['name'];
        $target_file = $target_path.$filename;
        $num = $_POST['num'];
        $num_chunks = $_POST['num_chunks'];

        move_uploaded_file($tmp_name, $target_file.$num);
        // count ammount of uploaded chunks
        $chunksUploaded = 0;
        for ( $i = 1; $i <= $num_chunks; $i++ ) {
            // Log::info($target_file.$i.' exists:'.file_exists( $target_file.$i ));
            if ( file_exists( $target_file.$i ) ) {
                $chunksUploaded++;
            }
        }
        // and THAT's what you were asking for
        // when this triggers - that means your chunks are uploaded
        if ($chunksUploaded == $num_chunks) {
            Log::info('Received all parts of the files');
            /* first we delete any eventual previous file */
            if (file_exists($target_file))
            {
                unlink($target_file);
            }
            /* here you can reassemble chunks together */
            for ($i = 1; $i <= $num_chunks; $i++) {
                $file = fopen($target_file.$i, 'rb');
                $buff = fread($file, 2097152);
                fclose($file);
        
                $final = fopen($target_file, 'ab');
                $write = fwrite($final, $buff);
                fclose($final);
                unlink($target_file.$i);
            }
            Log::info('File uploaded and reconstructed.');
            return $target_file;
        } else {
            return null;
        }
    }

    private function parseLinksCSV($filename)
    {
        $tmpLinks = array();
        if (($file = fopen($filename, 'r')) !== FALSE)
        {
            $header = fgetcsv($file);
            while(($line = fgetcsv($file)) !== false)
            {
                $movieId = $line[0];
                $imdbId  = $line[1];
                $tmdbId  = $line[2];
                array_push($tmpLinks, [
                    'movie_id'  => $movieId,
                    'imdb_id'   => $imdbId,
                    'tmdb_id'   => $tmdbId
                ]);
            }
            fclose($file);
        } else {
            Log::info('!!! Could not open file !!!');
        }
        Log::info('Adding links into DB');
        if (env('DB_CONNECTION') === 'sqlite') {
          foreach ($tmpLinks as $link) {
            $val = DB::table('links')->insert($link);
          }
        } else {
          $linkChunks = array_chunk($tmpLinks, 1024);
          foreach($linkChunks as $chunk)
          {
              $val = DB::table('links')->insert($chunk);
          }
        }
        Log::info('Links added into DB');
        return count($tmpLinks);
    }

    private function parseMovieCSV($filename)
    {   
        $tmpInsert = array();
        $tmpGenres = array();

        if (($file = fopen($filename, 'r')) !== FALSE)
        {
            $header = fgetcsv($file);
            while(($line = fgetcsv($file)) !== false)
            {
                $movieId = $line[0];
                $title = $line[1];
                $genres = explode('|',$line[2]);
                array_push($tmpGenres, array('movieId'=>$movieId, 'genres'=>$genres));
                array_push($tmpInsert, array('id'=>$movieId, 'title' =>$title));
            }
            fclose($file);
        } else {
            Log::info('!!! Could not open file !!!');
        }
        Log::info('Adding movies into DB');
        if (env('DB_CONNECTION') === 'sqlite') {
          foreach ($tmpInsert as $movie) {
            $val = DB::table('movies')->insert($movie);
          }
        } else {
          $movieChunks = array_chunk($tmpInsert, 1024);
          foreach($movieChunks as $chunk)
          {
              $val = DB::table('movies')->insert($chunk);
          }
        }
        
        
        Log::info('Movies added into DB');
        $this->processGenres($tmpGenres);

        return $val;
    }

    private function processGenres($tmpGenres)
    {
        $this->addGenresToDB($tmpGenres);
        $this->addGenreMovieLink($tmpGenres);
        Log::info('Genres added into DB');
    }

    private function addGenreMovieLink($tmpGenres)
    {
        Log::info('Adding genres into DB step 3');
        $genreMovieInserts = array(); // to reduce # inserts
        $genreToIds = array(); // use in order to reduce the # calls to DB
        $genres = DB::table('genres')->get();
        foreach ($genres as $g) {
            $genreToIds[$g->name] = $g->id;
        }
        Log::info('Adding genres into DB step 4');
        foreach($tmpGenres as $data) {
            $genres = $data['genres'];
            $movieId = $data['movieId'];
            foreach($genres as $genreStr) {
                $genreStr = str_replace(array("\r", "\n"), '', $genreStr);
                $genreId = $genreToIds[$genreStr];
                array_push($genreMovieInserts, array('movie_id'=>$movieId, 'genre_id'=>$genreId));
            }
        }
        Log::info('Adding genres into DB step 5');
        // mass insert all the links
        $chunks = array_chunk($genreMovieInserts, 1024);
        foreach($chunks as $chunk) {
            $val = DB::table('genre_movie')->insert($chunk);
        }
        // $val = DB::table('genre_movie')->insert($genreMovieInserts);
        Log::info('DONE Adding genres into DB!');
    }

    private function addGenresToDB($tmpGenres)
    {
        Log::info('Adding genres into DB');
        $genresUnique = array();
        // generate a unique array with all the genres
        foreach($tmpGenres as $data) {
            $genres = $data['genres'];
            foreach($genres as $genreStr) {
                $genreStr = str_replace(array("\r", "\n"), '', $genreStr);
                if (!isset($genresUnique[$genreStr])) {
                    $genresUnique[$genreStr] = array('name' =>$genreStr);
                }
            }
        }
        Log::info('Adding genres into DB step 2');
        $val = DB::table('genres')->insert($genresUnique);
    }

    /**
     * Dispatch importation of ratings csv to the queue.
     *
     * @deprecated should not use this anymore as it will possibly create very long runnig jobs.
     * @param [type] $file
     * @return void
     */
    private function parseRatingsCSV($file)
    {
        ImportRatingsCSV::dispatch($file);
        Log::info('Dispatched importRatingsCSV...');
        return response()->json([
            'data' => 'Processing...',
            'status' => 200
        ]);
    }
}
