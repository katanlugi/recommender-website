@extends('layouts.app')

@section('content')
<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Current Movie Library Instance</h3>
        </div>
        <div class="panel-body">
            <div class="list-group">
            {{--  <label for="userid" class="col-md-4 control-label">Your user ID</label>
                        <div name="userid">{{ Auth::user()->id }}</div>  --}}
                <ul class="list-group">
                    <li class="list-group-item">
                        <label for="nb-movies" class="col-md-4 control-label">Number of movies</label>
                        <div name="nb-movies">{{ $nbMovies }}</div>
                    </li>
                    <li class="list-group-item">
                        <label for="nb-movies" class="col-md-4 control-label">Number of ratings</label>
                        <div name="nb-movies">{{ $nbRatings }}</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">About</h2>
        </div>
        <div class="panel-body"> 
            <p>
                Demo Website using the <a href="https://github.com/katanlugi/recommender">item based recommender</a>.
                This is a demo website that allows you to test the recommender system using the MovieLens dataset.
            </p>
            <p>
                You can create an admin account and import the dataset and manage the different option of the recommender.
            </p>
            <p><strong>This code is a demo and is NOT production ready.</strong></p>
            <p>
              Using a MySQL database running on an SSD we were able to provide a usable experience
              running the full MovieLens dataset (20M ratings for 27k movies).
            </p>
        </div>
    </div>
</div>

</div>
@endsection