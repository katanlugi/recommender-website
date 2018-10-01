@extends('layouts.app')

@section('content')
<div class="col col-md-12">
<div class="movie-card">
    <div class="container">
        <a href="#"><img src="{{ $movie->getLocalMovieImagePath() }}" alt="cover" class="cover"></img></a>

        <div class="hero">
            <img src="{{ $movie->getLocalBackdropImage() }}" alr="hero" class="hero"></img>
            <div class="details">
                <div class="custom">
                    <h2>{{ $movie->getTitle() }}</h2>
                    <h4>{{ $movie->tagline }}</h4>
                    @if($movie->rating)
                        <my-rating :rating-default="{{ $movie->rating }}" :movie-id="{{ $movie->id }}"></my-rating>
                    @else
                        <my-rating :movie-id="{{ $movie->id }}"></my-rating>
                    @endif
                    {{--  <div class="movie-like">
                        @include('partials._ratings')
                    </div>  --}}
                </div>
            </div>
        </div>
        <div class="description">
            <div class="column1">
                @foreach ($movie->genres as $genre)
            <span class="tag"><a href="/category/{{ $genre->name }}">{{ $genre->name }}</a></span>
                @endforeach
            </div>
            <div class="column2">
                <p>{{ $movie->overview }}</p>
                <p><small>
                    {{ $movie->runtime }} min / {{ $movie->release_date }} / 
                    Average rating: {{ round($movie->averageRating(), 2) }}
                </small></p>
                {{--  <div class="avatars">
                    <a href="#" data-tooltip="Person 1" data-placement="top">
                        <img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/195612/hobbit_avatar1.png" alt="avatar1" />
                    </a>
                    <a href="#" data-tooltip="Person 2" data-placement="top">
                        <img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/195612/hobbit_avatar2.png" alt="avatar2" />
                    </a>
                    <a href="#" data-tooltip="Person 3" data-placement="top">
                        <img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/195612/hobbit_avatar3.png" alt="avatar3" />
                    </a>
                </div>  --}}
            </div>
        </div>
    </div>
</div>
</div>
@endsection
@section('footer')
    @parent
    <script>
        function selectRating(movieId, ratingValue) {
            let el = document.getElementById(`ratingId_${movieId}`);
            el.setAttribute('value',ratingValue);
        }

        function insertRating(movieId, ratingValue) {
            selectRating(movieId, ratingValue);
            console.log(`Sending rating ${ratingValue} for movieId ${movieId}`);
            
            let formData = new FormData();
            formData.append('movie_id', movieId);
            formData.append('rating', ratingValue);
            const _token = '{{ csrf_token() }}';
            ajaxRequest.makeAjaxCall('post', '{{ route("addRating") }}', formData, _token).then((val) => {
                console.log(val);
                console.log(val.response);
                return true;
            }).catch(e => {
                console.error(`ajax call failed... code: ${e}`);
            });
        }
    </script>
@stop