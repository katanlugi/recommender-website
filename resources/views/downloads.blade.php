@extends('layouts.app')

@section('content')
<div class="col-md-12">
    <h2>Downloads</h2>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Source Code</h3>
        </div>
        <div class="panel-body">
            <ul class="list-group">
                <li class="list-group-item">
                    <label for="exercise" class="control-label col-md-4">Exercise</label>
                    <a id="exercise" href="{{ URL::asset('/files/Recommender-base.zip') }}">Maven base project</a>
                </li>
                <li class="list-group-item">
                    <label for="solution" class="control-label col-md-4">Solution</label>
                    @if($shouldPublishSolutions)
                        <a id="solution" href="{{ URL::asset('/files/Recommender-solution.zip') }}">Maven Solution project</a>
                    @else
                        <span id="solution">Solution not yet published.</span>
                    @endif
                </li>
            </ul>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Dataset</h3>
        </div>
        <div class="panel-body">
            <ul class="list-group">
                <li class="list-group-item">
                    <label for="ratings" class="control-label col-md-4">Ratings (>20M)</label>
                    <a id="ratings" href="{{ route('dl-dataset') }}">data-set.csv</a>
                </li>
                <li class="list-group-item">
                    <label for="movies" class="control-label col-md-4">Movies (>27k)</label>
                    <a id="movies" href="{{ route('dl-movies') }}">movies.csv</a>
                </li>
                @can('importData')
                <li class="list-group-item">
                    <a id="movies" href="{{ route('update-dataset') }}" class="btn btn-primary">Refresh Ratings.csv</a>
                    <a id="movies" href="{{ route('update-movies') }}" class="btn btn-primary">Refresh Movies.csv</a>
                </li>
                @endcan
            </ul>
        </div>
    </div>
</div>
@endsection