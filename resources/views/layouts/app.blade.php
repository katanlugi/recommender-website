<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
    <link rel="dns-prefetch" href="//fonts.googleapis.com/">
    <link rel="dns-prefetch" href="//netdna.bootstrapcdn.com/">
    
</head>
<body>
    <div id="app">
        @include('partials._navbar')
        <div class="container">
            <div class="row row-offcanvas row-offcanvas-right">
            {{--  @auth  --}}
                <div class="col-6 col-md-3 sidebar-offcanvas" id="sidebar">
                    @include('partials._sidenav')
                </div>
            {{--  @endauth  --}}
                <div class="col-12 col-md-9">
                    @include('flash::message')
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            </div>
        </div>
        <footer>
            <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <p>&copy; 2018 <a href="https://ti.bfh.ch">Bern University of Applied Sciences</a></p>
                </div>
            </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="{{ mix('/js/app.js') }}"></script>
    <script src="{{ mix('/js/libs.js') }}"></script>

    @yield('footer')
</body>
</html>
