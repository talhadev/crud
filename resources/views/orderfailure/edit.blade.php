@extends('layout.master')

@section('template_title')
    Edit Failure Order
@endsection

@section('cssscript')

@endsection

@section('content')
	
	<div class="row">
		<div class="com-md-12">			

			<h3 class="text-muted text-center">Edit Order- {{ $store_name->name }}</h3>

			@include('orderfailure.error')
			
			{!! Form::model($failure_order, ['method' => 'PATCH', 'action' => ['OrderfailureController@update', $failure_order->id]]) !!}

				@include('orderfailure.form', ['sumbitButtonText' => 'Update Order', 'sumbitButtonTextProceed' => 'Update And Proceed']);

			{!! Form::close() !!}			
							
		</div>
	</div>	

@stop



