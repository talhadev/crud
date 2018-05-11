@extends('layout.master')

@section('template_title')
    Activate Action
@endsection

@section('content')
	
	<div class="row">
		<div class="col-md-12">				

			<h3 class="text-center text-muted">Activate Action</h3>

			@if(Session::has('error_flash'))
		        <div class="alert alert-danger col-md-8 col-md-offset-2">
		            {{ Session::get('error_flash') }}
		        </div>
		    @endif

		    @include('action.error')

			{!! Form::open(['url' => '/actions']) !!}

				@include('action.form', ['sumbitButtonText' => 'Activate Action'])

			{!! Form::close() !!}			

		</div>
	</div>

@stop

