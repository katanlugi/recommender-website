<div class="container">
   <div class="row">
      <div class="col-md-3">
        <div class="list-group">
            @foreach($genres as $genre)
                <a href="{{ route('category', ['id' => $genre->name]) }}" class="list-group-item">{{ $genre->name }}</a>
            @endforeach
        </div>
      </div>
   </div>
</div>