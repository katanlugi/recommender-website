@empty($movies)
    <div class="alert alert-info" role="alert">There is no movie to display...</div>
@endempty
<div class="movies-container">
    @foreach($movies as $movie)
        <div class="movie-item">
            @if( isset($movie->movie))
                @include('partials._movieCard', ['movie' => $movie->movie])
            @else
                @include('partials._movieCard')
            @endif
        </div>
    @endforeach
</div>
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