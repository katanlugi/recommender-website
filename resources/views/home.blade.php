@extends('layouts.app')

@section('content')
{{--  <div class="container">
<div class="col-md-9">  --}}
    <movies-grid title="{{ $title }}" url="/api/movies/last/9"></movies-grid>
{{--  </div>
</div>  --}}
@endsection
