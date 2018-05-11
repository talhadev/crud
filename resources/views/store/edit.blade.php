@extends('layout.master')

@section('template_title')
    Edit Store
@endsection

@section('cssscript')

@endsection

@section('content')
	
	<div class="row">
		<div class="com-md-12">			

			<h3 class="text-muted text-center">Edit - {{ $store->name }}</h3>

			@if(Session::has('error_flash'))
		        <div class="alert alert-danger col-md-8 col-md-offset-2">
		            {{ Session::get('error_flash') }}
		        </div>
		    @endif

		    @include('store.error')
			
			{!! Form::model($store, ['method' => 'PATCH', 'action' => ['StoresController@update', $store->id]]) !!}

				@include('store.form', ['sumbitButtonText' => 'Update Store'])

			{!! Form::close() !!}			
							
		</div>
	</div>	

@stop



