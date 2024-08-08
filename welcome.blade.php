@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body row justify-content-center">
                        @auth
                            <a href="{{ route('login') }}" class="btn btn-primary">До рейтингу</a>
                        @else
                            <a href="{{ route('google.redirect') }}" class="btn btn-primary"> Login with Google </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
