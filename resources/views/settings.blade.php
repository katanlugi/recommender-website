@extends('layouts.app')

@section('content')
    <div class="col-md-12">
        <h2>Settings</h2>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Personal Settings</h3>
            </div>
            <div class="panel-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <label for="userid" class="col-md-4 control-label">Your user ID</label>
                        <div name="userid">{{ Auth::user()->id }}</div>
                    </li>
                    <li class="list-group-item">
                        <div class="form-group">
                            <label for="resetRatings" class="col-md-4 control-label">You have rated {{ $nbRatedMovies }} movie(s)</label>
                            <button type="button" name="resetRatings" onclick="reset()" class="btn btn-danger">Reset all your ratings</button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    @parent
    <script>
        function reset() {
            const url = "{{ route('resetRatings') }}";
            const token = '{{ csrf_token() }}';
            ajaxRequest.makeAjaxCall(
                'post',
                url,
                null,
                token
            ).then(rsp => console.log(rsp))
            .catch(err => console.error(err));
        }
    </script>
@stop