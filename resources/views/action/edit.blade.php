@extends('layout.master')

@section('template_title')
    Edit Action
@endsection

@section('content')
	
	<div class="row">
		<div class="com-md-12">			

			<h3 class="text-muted text-center">Edit - {{ $action->method }}</h3>

			@if(Session::has('error_flash'))
		        <div class="alert alert-danger col-md-8 col-md-offset-2">
		            {{ Session::get('error_flash') }}
		        </div>
		    @endif

		    @include('action.error')
			
			{!! Form::model($action, ['method' => 'PATCH', 'action' => ['ActionController@update', $action->id]]) !!}

				@include('action.form', ['sumbitButtonText' => 'Update Action']);

			{!! Form::close() !!}			
							
		</div>
	</div>	

@stop



