@extends('layouts.app')

@section('content')
    <div class="col-md-12">
        <h2>Admin Settings</h2>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Jobs Dashboard</h3>
            </div>
            <div class="panel-body">
                Open Horizon Dashboard in a new tab: <a href="{{ url('/horizon') }}" target="_blank" rel="noopener">Horizon Dashboard</a>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Recommender Settings</h3>
            </div>
            <div class="panel-body">
                <div class="alert alert-info" role="alert">
                  The recommended configuration for the folowing parmeters is: true, true, false, 20, 20
                </div>
                <form action="{{ route('apply-changes') }}" method="post">
                    {{ csrf_field() }}
                    <ul class="list-group">
                        <li class="list-group-item">
                            <label for="force_train" class="control-label col-md-8">Force re-train the model even if one is already precomputed</label>
                            <input type="checkbox" name="force_train" {{ $prefs['force_train'] == 1 ? 'checked' : '' }}>
                        </li>
                        <li class="list-group-item">
                            <label for="implicit_pref" class="control-label col-md-8">Use implicit ratings</label>
                            <input type="checkbox" name="implicit_pref" {{ $prefs['implicit_pref'] == 1 ? 'checked' : '' }}>
                        </li>
                        <li class="list-group-item">
                            <label for="set_non_negative" class="control-label col-md-8">Use nonNegative ratings (best when inverse of implicit ratings)</label>
                            <input type="checkbox" name="set_non_negative" {{ $prefs['set_non_negative'] == 1 ? 'checked' : '' }}>
                        </li>
                        <li class="list-group-item">
                            <label for="num_iterations" class="control-label col-md-8">Number of training iterations</label>
                            <input type="number" name="num_iterations" value="{{ $prefs['num_iterations'] }}">
                        </li>
                        <li class="list-group-item">
                            <label for="num_features" class="control-label col-md-8">Number of features</label>
                            <input type="number" name="num_features" value="{{ $prefs['num_features'] }}">
                        </li>
                    </ul> 
                    <button type="submit" class="btn btn-primary">Apply changes</button>
                </form>
            </div>
            <div class="panel-footer">
                <button type="button" name="reset" onclick="resetSettings()" class="btn btn-danger">Reset to default settings</button>
            </div>
        </div>
        <admin-recom-server></admin-recom-server>
    </div>
@endsection

@section('footer')
    @parent
    <script>
        const token = '{{ csrf_token() }}';

        function resetSettings() {
            url = "{{ route('reset-settings') }}";
            ajaxRequest.makeAjaxCall(
                'post',
                url,
                null,
                token
            ).then(rsp => {
                console.log(rsp.response);
                //location.reload();
            })
            .catch(err => console.error(err));
        }
    </script>
@stop