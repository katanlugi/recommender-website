<div class="movie">
    <a href="{{ route('showMovie', ['id' => $movie->id]) }}">
    <img class="movie-img" src="{{ $movie->getLocalMovieImagePath() }}"></img>
    </a>
    <div class="text-movie-cont">
        <div class="mr-grid">
            <div class="col1">
                <h1>{{ $movie->getTitle() }}</h1>
                <ul class="movie-gen">
                    <li>{{ $movie->runtime }} min /</li>
                    <li>{{ $movie->release_date }} /</li>
                    <li>
                    @foreach ($movie->genres as $genre)
                        {{ $genre->name }}@if (!$loop->last), @endif
                    @endforeach
                    </li>
                    <li>{{ $movie->imdb_id }}</li>
                    <li>Average rating: {{ round($movie->averageRating(), 2) }}</li>
                </ul>
            </div>
        </div>
        <div class="mr-grid summary-row">
            {{--  <div class="col2">  --}}
                <h5>{{ $movie->tagline }}</h5>
            {{--  </div>  --}}
            <div class="col2>">
                <!--
                <ul class="movie-likes">
                    <li><i class="material-icons">&#xE813;</i>124</li>
                    <li><i class="material-icons">&#xE813;</i>3</li>
                </ul>
                -->
                <div class="movie-like">
                    @include('partials._ratings')
                </div>
            </div>
        </div>
        {{--  <div class="mr-grid actors-row">
            <div class="col1">
                <p class="movie-actors">list of actors...</p>
            </div>
        </div>  --}}
        <div class="mr-grid action-row">
            <div class="col2">
                <div class="watch-btn">
                    <h3>
                        <a href="{{ route('showMovie', ['id' => $movie->id]) }}"><i class="glyphicon glyphicon-play"></i>MORE</a>
                    </h3>
                </div>
                {{--  <div class="col6 action-btn">
                    <i class="material-icons">&#xE161;</i>
                </div>
                <div class="col6 action-btn">
                    <i class="material-icons">&#xE866;</i>
                </div>
                <div class="col6 action-btn">
                    <i class="material-icons">&#xE80D;</i>
                </div>  --}}
            </div>
        </div>
    </div>
</div>