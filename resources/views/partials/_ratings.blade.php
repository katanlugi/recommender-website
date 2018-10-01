<form id="form_{{ $movie->id }}" 
    name="form_{{ $movie->id }}"
    method="post"
    action="{{ route("addRating") }}">

    {{--  <p style="color: red;">{{ $movie->rating }}</p>  --}}
    <fieldset class="rating">
        <input type="text" id="movieId" name="movieId" value="{{ $movie->id }}">
        {{--  <input type="text" id="ratingId_{{ $movie->id }}" name="rating" value="">  --}}
        <input type="number" id="ratingId_{{ $movie->id }}" name="rating">
        {{--  <input type="submit" id="submitId_{{ $movie->id }}" name="submitId_{{ $movie->id }}" value="submit">  --}}
        {{ csrf_field() }}
        
        @foreach($rates as $rate)
            @if(isset($movie->rating) && $rate == $movie->rating)
                <input  type="radio"
                        id="rat_{{ $movie->id }}{{ $rate }}"
                        name="rating"
                        value="{{ $rate }}"
                        checked>
            @else
                <input  type="radio"
                        id="rat_{{ $movie->id }}{{ $rate }}"
                        name="rating"
                        value="{{ $rate }}">
            @endif
            @if(ends_with($rate, "5"))
                <label
                    onclick="insertRating({{ $movie->id }}, {{ $rate }})"
                    for="rat_{{ $movie->id }}{{ $rate }}"
                    class="half"
                ></label> 
            @else
                <label
                    onclick="insertRating({{ $movie->id }}, {{ $rate }})"
                    for="rat_{{ $movie->id }}{{ $rate }}"
                    class="full"
                ></label>
            @endif
        @endforeach
        @if($movie->rating)
        
        <a href="{{ route('removeRating', ['movie_id'=>$movie->id]) }}"><span class="glyphicon glyphicon-remove-circle"></a>
        @endif
    </fieldset>
</form>