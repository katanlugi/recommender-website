<nav class="navbar navbar-default navbar-static-top">
   <div class="container">
      <div class="navbar-header">

         <!-- Collapsed Hamburger -->
         <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
            <span class="sr-only">Toggle Navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
         </button>

         <!-- Branding Image -->
         <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name', 'Laravel') }}
         </a>
      </div>

      <div class="collapse navbar-collapse" id="app-navbar-collapse">
         <!-- Left Side Of Navbar -->
         <ul class="nav navbar-nav">
            <li><a href="{{ route('allMovies') }}">New Movies</a></li>
            <li><a href="{{ route('topMovies') }}">Top Movies</a></li>
            <li><a href="{{ route('about') }}">About Mahout</a></li>
            <li><a href="{{ route('myProfile') }}">Your Recommendations</a></li>
            @auth
            <li><a href="{{ route('downloads') }}">Downloads</a></li>
            @endauth
         </ul>

         <!-- Right Side Of Navbar -->
         <ul class="nav navbar-nav navbar-right">
            <li>
                <div class="dropdown">
                    <form class="navbar-form navbar-left search">
                        <div class="input-group">
                            <input type="text" class="form-control dropdown-toggle" name="search" placeholder="Search"
                                data-toggle="dropdown">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" onclick="searchMovie()" name="search">Go!</button>
                            </span>
                        </div>
                        {{--  <button type="submit" class="btn btn-default" onclick="search()">Submit</button>  --}}
                    </form>
                    
                    <div class="dropdown-menu-search"></div>
                    {{--  <ul class="dropdown-menu-search"></ul>  --}}
            </li>
            <!-- Authentication Links -->
            @guest
                <li><button type="button" class="btn btn-danger navbar-btn" onclick="resetGuest()">Reset Guest</button></li>
                <li><a href="{{ route('login') }}">Login</a></li>
                {{--  <li><a href="{{ route('register') }}">Register</a></li>  --}}
            @else
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                    {{ Auth::user()->username }} <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" role="menu">
                        <li>
                            <a href="{{ route('settings') }}"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> Settings</a>
                        </li>
                        @can('importData')
                            <li>
                                <a href="{{ route('import-data') }}"><span class="glyphicon glyphicon-import" aria-hidden="true"></span> Import new Data</a>
                            </li>
                        @endcan
                        @can('changeRecommenderServer')
                            <li>
                                <a href="{{ route('admin-settings') }}"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Admin Settings</a>
                            </li>
                        @endcan
                        <li role="separator" class="divider"></li>
                        <li>
                            <a href="{{ route('logout') }}"
                                onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                                <span class="glyphicon glyphicon-log-out" aria-hidden="true"></span> Logout
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                {{ csrf_field() }}
                            </form>
                        </li>
                    </ul>
                </li>
            @endguest
         </ul>
      </div>
   </div>
</nav>

@section('footer')
@parent
<script>
    @guest
    const token = '{{ csrf_token() }}';

    function resetGuest() {
        console.log('guest mode.');
        url = "{{ route('resetSession') }}";
        ajaxRequest.makeAjaxCall(
            'post',
            url,
            null,
            token
        ).then(rsp => {
            console.log(rsp.response);
            location.reload();
        })
        .catch(err => console.error(err));
    }
    @endguest

    const searchField = document.querySelector("input[name='search']");
    if (searchField) {
        searchField.addEventListener("keyup", search);
    }
    
    //const el = document.querySelector('ul[class="dropdown-menu-search"]')
    const el = document.querySelector('div[class="dropdown-menu-search"]')
    function showSearchResults() {
        //const el = document.querySelector('ul[class="dropdown-menu-search"]')
        if(el.classList.contains('hide')){
            el.classList.remove('hide');
        }
        if(!el.classList.contains('show')){
            el.classList.add('show');
            
            loading.displayLoading(el);
            //el.appendChild(loadingLi);
        }
    }
    function hideSearchResults() {
        //const el = document.querySelector('ul[class="dropdown-menu-search"]')
        if(el.classList.contains('show')){
            el.classList.remove('show');
        }
        if(!el.classList.contains('hide')){
            el.classList.add('hide');
        }
    }

    function populateSearchResults(movies) {
        loading.hideLoading(el);
        el.innerHTML = "";
        movies = JSON.parse(movies);
        
        for(let i in movies) {
            const m = movies[i];
            if(i > 6) {
                break;
            }
            el.innerHTML += `<li><a href="/movies/${m['id']}">${m['title']}</a></li>`;
        }
    }
    function searchMovie() {
        const str = document.querySelector("input[name='search']").value;
        window.location.href = `/search/contains/${str}`;
    }
    function search() {
        const str = document.querySelector("input[name='search']").value;
        /*
        if (str.length < 3){
            return;
        }
        */
        const baseUrl = "{{ url('search') }}";
        const url = `${baseUrl}/startsWith/${str}/7`;
        const token = '{{ csrf_token() }}';
        console.log(`url: ${url}`);
        ajaxRequest.makeAjaxCall(
            'post',
            url,
            str,
            token
        ).then(rsp => {
            showSearchResults();
            populateSearchResults(rsp.response);
        }).catch(err => console.error(err));
    }
</script>
@stop