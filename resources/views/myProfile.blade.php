@extends('layouts.app')

@section('content')
<div class="col-md-12">
  <p>Your id: {{ $userId }}</p>
  <ul class="nav nav-tabs nav-justified">
    <li id="tab-1" class="active"><a data-toggle="tab" href="#recom">Your recommendations</a></li>
    <li id="tab-2"><a data-toggle="tab" href="#ratings">Your ratings</a></li>
  </ul>
</div>
<div class="col-md-12">
  <div class="tab-content">
    <div id="recom" class="tab-pane fade in active">
      <recommendations title="{{ $title }}" url="{{ $url }}">
      </recommendations>
      {{-- <movies-grid title="{{ $title }}"
          url="{{ $url }}"
          paginated="{{ false }}">
      </movies-grid> --}}
    </div>
    <div id="ratings" class="tab-pane fade">
      <movies-grid title="Your Ratings"
          paginated="{{ $paginated }}"
          url="{{ route('getRatedMovies') }}">
      </movies-grid>
    </div>
  </div>
</div>
@endsection

{{--  @section('footer')
  @parent
  <script>
      document.addEventListener("DOMContentLoaded", function() {
          const url = window.location.href;
          if (url.includes('?page')) {
              $('.nav-tabs a[href="#ratings"]').tab('show');
          } else {
              $('.nav-tabs a[href="#recom"]').tab('show');
          }
      });
  </script>
@stop  --}}