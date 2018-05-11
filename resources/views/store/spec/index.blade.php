@extends('layout.master')

@section('template_title')
    Store Specs
@endsection

@section('content')
	
	<?php $counter = 1; ?>
	
	<h3 class="text-muted text-center">Store Specs</h3>

	@if(Session::has('flash_message'))
        <div class="alert alert-success">
            {{ Session::get('flash_message') }}
        </div>
    @endif

    @if(Session::has('error_flash'))
        <div class="alert alert-danger">
            {{ Session::get('error_flash') }}
        </div>
    @endif
	
	@if( count($specs) > 0 )	
		<table class="table table-striped table-hover table-responsive table-condensed table-layout">
		<tr>
			<th>S NO 	 </th>
			<th>Store 	 </th>			
			<th>Email 	 </th>			
			<th>Shipping </th>			
			<th>View  	 </th>			
			<th>Action	 </th>
		</tr>
		@foreach( $specs as $store_id => $spec)			

			<tr>
				<td>{{ $counter }}					   </td>
				<td>{{ $spec['store_info']['name'] }}  </td>
				<td>{{ $spec['store_info']['email'] }} </td>
				<td>Shipping Details 				   </td>			
				<td>View Details 					   </td>	
				<td>
					<a href="{{$store_id}}" title="edit store spec" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span></a>
					<a href="{{$store_id}}" title="show complete info" class="btn btn-info"><span class="glyphicon glyphicon-eye-open"></span></a>
				</td>
			</tr>

			<?php $counter+=1; ?>
		@endforeach
		</table>

	@else
		<p class="text-muted">No store spec yet</p>
	@endif	

@stop