@extends('layouts.app')

@section('content')
    <div class="col-md-12">
        <h2>Data Importer</h2>
        <div class="progress" style="display: none;">
            <div class="progress-bar" role="progressbar" 
                aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;">
                60%
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Import movies.csv</h3>
            </div>
            <div class="panel-body">
                <form id="movie-importer"
                    name="movie-importer"
                    enctype="multipart/form-data">
                    
                    <ul class="list-group">
                        <li class="list-group-item"><strong>Data Format:</strong> 
                            movieId,title,genres --> 2,Jumanji (1995),Adventure|Children|Fantasy
                        </li>
                    </ul>
                    <div class="form-group">
                        <label for="movieFile" class="col-md-4 control-label">CSV file to import</label>
                        <input type="file" name="movieFile" id="movieFile" required>
                    </div>
                    <button type="button" onclick="sendMovieFile()" class="btn btn-success">Import Movies</button>
                </form>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Import links.csv</h3>
            </div>
            <div class="panel-body">
                <form id="movie-links-importer"
                    name="movie-links-importer"
                    enctype="multipart/form-data">
                    <ul class="list-group">
                        <li class="list-group-item"><strong>Data Format:</strong> movie_id, imdb_id, tmdb_id</li>
                    </ul>
                    <div class="form-group">
                        <label for="linksFile" class="col-md-4 control-label">CSV file to import</label>
                        <input type="file" name="linksFile" id="linksFile" required>
                    </div>
                    <button type="button" onclick="sendLinksData()" class="btn btn-success">Import Links</button>
                </form>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Import ratings.csv</h3>
            </div>
            <div class="panel-body">
                <form id="ratings-importer"
                    name="ratings-importer"
                    enctype="multipart/form-data">
                    <ul class="list-group">
                        <li class="list-group-item"><strong>Data Format:</strong> user_id,movie_id,rating,timestamp</li>
                    </ul>
                    <div class="form-group">
                        <label for="ratingsFile" class="col-md-4 control-label">CSV file to import</label>
                        <input type="file" name="ratingsFile" id="ratingsFile" required>
                    </div>
                    <div class="alert alert-info" role="alert">Once uploaded the file will be processed by 
                        <a href="{{ url('/horizon') }}" target="_blank" rel="noopener">Laravel Horizon</a> and can 
                        last <strong>>30min</strong> depending on the connection and the anount of ratings.
                    </div>
                    <button type="button" onclick="sendData()" class="btn btn-success">Import Ratings</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <h2>Consolidate Database</h2>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Missing metadata</h3>
            </div>
            <div class="panel-body">
                <p>{{ $nbUnconsolidatedMovies }} movies are still missing metadata.</p>
                <div class="alert alert-info" role="alert">Will be processed by 
                    <a href="{{ url('/horizon') }}" target="_blank" rel="noopener">Laravel Horizon</a> and can 
                    last <strong>>30min</strong> depending on the connection and the number of movies.
                </div>
                <button type="button" onclick="consolidate()" class="btn btn-warning">Retrieve Aditional Data</button>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Precompute average ratings</h3>
            </div>
            <div class="panel-body">
                <p>{{ $nbMissingAvgRating }} movies still don't have a computed average rating.</p>
                <div class="form-group">
                <div class="alert alert-info" role="alert">Will be processed by 
                    <a href="{{ url('/horizon') }}" target="_blank" rel="noopener">Laravel Horizon</a>
                    and will take <strong>~16 minutes</strong> for 20M ratings...
                </div>
                <button type="button" onclick="computeAvgRatings()" class="btn btn-warning">Compute</button>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Missing category</h3>
            </div>
            <div class="panel-body">
                <p>{{ $nbMissingCategory }} movies don't have a category... (Movies listed under the category "(no genres listed)".</p>
                <div class="alert alert-danger" role="alert">
                    <strong>TODO</strong> Not implemented yet...
                </div>
            </div>
        </div>
    </div>
    
@endsection

@section('footer')
    @parent
    <script>
        function computeAvgRatings() {
            const url = "{{ route('computeAvgRatings') }}";
            const token = '{{ csrf_token() }}';
            ajaxRequest.makeAjaxCall(
                'post',
                url,
                null,
                token
            ).then(rsp => console.log(rsp))
            .catch(err => console.error(err));
        }

        function consolidate() {
            const url = "{{ route('consolidate-db') }}";
            const token = '{{ csrf_token() }}';
            
            ajaxRequest.makeAjaxCall(
                'post',
                url,
                null,
                token
            )
            .then(/*rsp => console.log(rsp)*/)
            .catch(err => console.error(err));
        }
        function sendMovieFile() {
            console.log('send movies');
            const file = document.getElementById('movieFile').files[0];

            console.log(file);
            const url = "{{ route('import-movie') }}";
            const token = '{{ csrf_token() }}';
            upload(file, url, token);
        }
        function sendLinksData() {
            console.log('send links');
            const file = document.getElementById('linksFile').files[0];

            console.log(file);
            const url = "{{ route('import-links') }}";
            const token = '{{ csrf_token() }}';
            upload(file, url, token);
        }
        function sendData() {
            console.log('send ratings');
            const file = document.getElementById('ratingsFile').files[0];
            
            console.log(file);
            const url = '{{ route("import-ratings") }}';
            const token = '{{ csrf_token() }}';
            upload(file, url, token);
        }

        function showProgress() {
            const bar = document.querySelector("div[class='progress']");
            bar.style.display = "block";
            // reset the progress to 0%
            updateProgress(0);
        }

        function updateProgress(valueNow) {
            const bar = document.querySelector("div[class='progress-bar']");
            bar.style.width = `${valueNow}%`;
            bar.setAttribute['aria-valuenow'] = valueNow;
            bar.textContent = `${valueNow}%`;
        }

        function upload(file, url, token) {
            console.log('preparing chunk upload');
            showProgress();
            const BYTES_PER_CHUNK = parseInt(2097152, 10);
            const size = file.size,
            NUM_CHUNKS = Math.max(Math.ceil(size / BYTES_PER_CHUNK), 1);
            let start = 0;
            let end = BYTES_PER_CHUNK;
            let num = 1;

            var chunkUpload = function(blob) {
                var data = new FormData();
                data.append('upload', blob, file.name);
                data.append('num', num);
                data.append('num_chunks', NUM_CHUNKS);

                ajaxRequest.makeAjaxCall(
                        'post',
                        url,
                        data, 
                        token
                    )
                    .then(/*rsp => console.log(rsp)*/)
                    .catch(err => console.error(err));
            }

            while (start < size) {
                // console.log(`uploading chunk ${start}-${end}`);
                chunkUpload(file.slice(start, end));
                start = end;
                end = start + BYTES_PER_CHUNK;
                num++;
                updateProgress(Math.ceil(num/NUM_CHUNKS));
            }
            updateProgress(Math.ceil(num/NUM_CHUNKS));
        }
    </script>
@stop