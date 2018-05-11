@extends('layout.master')

@section('template_title')
    Signup Store
@endsection

@section('cssscript')
	
@endsection

@section('content')
	
	<div class="row">
		<div class="col-md-12">				

			<h3 class="text-center text-muted">Activate Store</h3>

			@if(Session::has('error_flash'))
		        <div class="alert alert-danger col-md-8 col-md-offset-2">
		            {{ Session::get('error_flash') }}
		        </div>
		    @endif

		    @include('store.error')

			{!! Form::open(['url' => '/store']) !!}

				@include('store.form', ['sumbitButtonText' => 'Activate Store'])

			{!! Form::close() !!}			

		</div>
	</div>

@stop

