<?php

namespace App\Http\Controllers;

use App\Movie;
use App\Rating;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    private $items = null;
    private $handle = null;

    public function index()
    {
        $publishDate = Carbon::createFromDate(2017, 10, 6, 'Europe/Zurich');
        $currentDate = Carbon::now();
  
        $shouldPublishSolutions = $publishDate <= $currentDate;
        
        return view('downloads', compact(['shouldPublishSolutions']));
    }
    public function downloadDataset()
    {
        $filename = $this->generateFileName('data-set', 'csv');
        if ( file_exists( $filename ) ) {
            return $this->downloadCsv($filename);
        }else{
            return response()->json([
                'status' => 404,
                'message' => 'The data-set could not be found.'
            ]);
        }
    }

    public function downloadMovies()
    {
        $filename = $this->generateFileName('movies', 'csv');
        if ( file_exists( $filename ) ) {
            return $this->downloadCsv($filename);
        }else{
            return response()->json([
                'status' => 404,
                'message' => 'The movie set could not be found.'
            ]);
        }
    }

    public function updateMovies()
    {
        $this->items = ['id', 'title', 'release_date'];
        $filename = $this->generateFileName('movies', 'csv');
        
        $this->handle = fopen($filename, 'w+');

        Movie::select($this->items)
            ->chunk(32768, function($movies) {
                $this->buildCSV($movies);
            });
        fclose($this->handle);
        return $this->downloadCsv($filename);
    }

    public function updateDataset()
    {
        $this->items = ['user_id', 'movie_id', 'rating'];
        $filename = $this->generateFileName('data-set', 'csv');
        
        $this->handle = fopen($filename, 'w+');
        
        Rating::select($this->items)
            ->chunk(32768, function($ratings) {
                $this->buildCSV($ratings);
            });

        fclose($this->handle);
        return $this->downloadCsv($filename);
    }

    private function generateFileName(string $baseName, string $extension)
    {
        return $baseName.'.'.$extension;
    }

    private function buildCSV($data)
    {
        $size = count($this->items);
        foreach($data as $d) {
            $arr = array();
            for ($i=0; $i < $size; $i++) { 
                array_push($arr, $d[$this->items[$i]]);
            }
            fputcsv($this->handle, $arr);
        }
        fputcsv($this->handle, $arr);
    }

    private function downloadCSV(string $filename)
    {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=data-set.csv',
            'Expires'             => '0',
            'Pragma'              => 'no-cache'
        ];

        return response()->download($filename, $filename, $headers);
    }
}
