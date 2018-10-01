@extends('layouts.app')

@section('content')
{{--  {{rates}}  --}}
<movies-grid title="{{ $title }}" paginated="{{ $paginated }}" url="{{ $url }}"></movies-grid>

@endsection