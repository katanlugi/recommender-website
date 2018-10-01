@extends('layouts.app')

@section('content')
    <loading v-if="this.isLoading">
        <div slot="title">Loading</div>
        <div slot="description">[This may take 30 sec or more...]</div>
    </loading>
    <movies-grid category="Test Category" url="/getRecom"></movies-grid>
@endsection