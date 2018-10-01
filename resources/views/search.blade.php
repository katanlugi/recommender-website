@extends('layouts.app')

@section('content')
<div class="col-md-12">
    <div class="page-header-container">
        <div>
            <h2>Search</h2>
        </div>
    </div>
    <div class="row">
        <div class="panel panel-default">
            <!-- Default panel contents -->
            <div class="panel-heading">
                <div class="input-group">
                    <input type="text" class="form-control dropdown-toggle" name="search-field" placeholder="Search"
                        data-toggle="dropdown">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button" onclick="newSearch()" name="search">Go!</button>
                    </span>
                </div>
                <label class="radio inline">
                    <input checked="checked" name="type" type="radio" value="startsWith" id="startsWith">
                    <span>Starts with</span>
                </label>
                <label class="radio inline">
                    <input name="type" type="radio" value="endsWith" id="endsWith">
                    <span>Ends with</span>
                </label>
                <label class="radio inline">
                    <input name="type" type="radio" value="contains" id="contains">
                    <span>Contains</span>
                </label>
                <label class="radio inline">
                    <input name="type" type="radio" value="exact" id="exact">
                    <span>Exact</span>
                </label>
            </div>
            
            <!-- Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movies as $movie)
                        <tr>
                            <td>{{ $movie->id }}</td>
                            <td><a href="/movies/{{ $movie->id }}">{{ $movie->title }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        @if($movies instanceof \Illuminate\Pagination\LengthAwarePaginator )
            {{ $movies->links() }}
        @endif
    </div>
</div>
@endsection

@section('footer')
@parent
<script>
    //pre populate the search fields with data from the url
    const searchString = decodeURI(location.pathname.split('/')[3])
    const searchInput= document.querySelector("input[name='search-field']");
    const searchInputNav = document.querySelector("input[name='search']");
    
    searchInput.value = searchString;
    searchInputNav.value = searchString;

    // preselect the radio with data from the url
    const searchType = location.pathname.split('/')[2];
    document.getElementById(searchType).checked = true;


    function newSearch() {
        const str = document.querySelector("input[name='search-field']").value;
        const searchType = document.querySelector("input[name='type']:checked");

        window.location.href = `/search/${searchType.value}/${str}`;
    }
</script>
@stop