@extends('layout.master')

@section('template_title')
    Manage Stores
@endsection

@section('content')
	<?php $counter = 1; ?>
	
	<h3 class="text-muted text-center">Stores 
		<a href="/store/create" title="vendor shipping settings">
			<i class="fa fa-plus pull-right"></i>
		</a>
	</h3>

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
	
    <div class="col-md-6 col-md-offset-3">
	    <div class="form-group has-feedback">
		    {!! Form::text('search', null, ['class' => 'form-control', 'id' => 'search',  'placeholder' => 'search..' ]) !!}
		    <span class="glyphicon glyphicon-search form-control-feedback" aria-hidden="true"></span>
		</div>
	</div>

	@if( count($stores) > 0 )	
		<table class="table table-striped table-hover table-responsive table-condensed table-layout">
		<tr>
			<th>S NO 		  </th>
			<th>Store ID </th>
			<th>Platform 	  </th>
			<th>Name 	  	  </th>
			<th>Email 	 	  </th>
			<th>Telephone 	  </th>
			<th>Password  	  </th>
			<th>Uuid 	</th>
			<th>Action		  </th>
		</tr>
		<tbody id="stores">
			@foreach( $stores as $store)			

				<tr>
					<td>{{ $counter }}				</td>
					<td>{{ $store->technify_store_id }} </td>
					<td> <a href="{{$store->store_url}}" class="label label-default" target="_blank"> {{ $store->platforms }} </a> </td>
					<td>{{ $store->name }}  		</td>
					<td>{{ $store->email }}  		</td>
					<td>{{ $store->telephone }}  	</td>
					<td>{{ $store->password }}		</td>
					<td>{{ $store->uuid }}	</td>
					<td>
						<div class="btn-group">
							<a href="/store/{{$store->id}}/edit" title="edit store" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-pencil"></i></a>				
			                <a class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
						   		<i class="caret"></i>
						  	</a>
			                <ul class="dropdown-menu" role="menu">
						    	<li><a href="/store/{{$store->id}}" title="show complete info"><i class="glyphicon glyphicon-eye-open"></i>&nbsp;Show</a></li>	
						    	<li><a href="/store/pull/order/status/{{$store->uuid}}" title="pull order status"><i class="glyphicon glyphicon-arrow-down"></i>&nbsp;Pull Order Status</a></li>
						    	<li><a href="javascript:;" title="shipping settings" onclick="shippingSetttings({{$store->technify_store_id}})"><i class="glyphicon glyphicon-cog"></i>&nbsp;Shipping Settings</a></li>
						    	<li role="separator" class="divider"></li>
						    	<li><a href="javascript:;" title="delete store" data-toggle="modal" data-target="#dlt_store-{{$store->id}}"><i class="glyphicon glyphicon-trash"></i>&nbsp;Delete</a></li>
						  	</ul>
					  	</div>
					</td>
				</tr>

				<!-- Modal for delete data -->
				<div id="dlt_store-{{$store->id}}" class="modal fade" role="dialog">
				  	<div class="modal-dialog">				    
					    <div class="modal-content">
						    <div class="modal-header text-danger bg-danger">
						    	<button type="button" class="close" data-dismiss="modal">&times;</button>
						        <h4 class="modal-title">Delete Store</h4>
					        </div>
						    <div class="modal-body">
						        <p>Are you sure you want to Delete?</p>
						    </div>
						    <div class="modal-footer">
						        {!! Form::open(['url' => 'store/' . $store->id]) !!}
				                    {!! Form::submit('Yes', ['class' => 'btn btn-danger']) !!}
			               			{!! Form::hidden('_method', 'DELETE') !!}
				                {!! Form::close() !!}
						        <a href="javascript:;" class="btn btn-default" data-dismiss="modal">No</a>
						    </div>
					    </div>
				  	</div>
				</div>

				<?php $counter+=1; ?>
			@endforeach
		</tbody>
		</table>

		<!-- pagination -->
		@if( $stores->render() )
			<div class="pull-right">
				<div class="text-muted">
					Showing {{ $stores->toArray()['from'] }} - {{ $stores->toArray()['to'] }} of {{ $stores->toArray()['total'] }}
				</div>
			</div>

			<div class="text-center">
				<div class="pagination">{!! $stores->render() !!}</div>
			</div>
			<div class="clearfix"></div>
		@endif	

	@else
		<p class="text-muted">No store yet</p>
	@endif

	<a href="/store/create" class="btn btn-link">create new store</a>		

	<!-- Modal for vendor shipping settings -->
	<div id="shipping_settings" class="modal fade" role="dialog"> 
	  	<div class="modal-dialog">				    
		    <div class="modal-content">
			    <div class="modal-header bg-primary">
			    	<button type="button" class="close" data-dismiss="modal">&times;</button>
			        <h4 class="modal-title">Shipping Settings</h4>
		        </div>
			    <div class="modal-body"> 
			        @include('store.shippingSettingsForm', ['sumbitButtonText' => 'Add'])
			    </div>
		    </div>
	  	</div>
	</div>

<script type="text/javascript">

$(document).ready(function(){
  	$("#search").on("keyup", function() {
    	var value = $(this).val().toLowerCase();
    	$("#stores tr").filter(function() {
      		$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    	});
  	});
});

$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip(); 
});

// vendor shipping settings
function shippingSetttings(store_id) {		
	$.ajax({
        type: 'GET',
        url: 'shippingsettings/' + store_id,
        dataType: 'json',
        data: {},
        beforeSend: function () {

        },
        complete: function () {

        },
        success: function (response) {  
            if(response['response']) {            	
            	
            	$('#shipping_settings').modal();
            	$('#shipping_settings #store_id').val(store_id);
                $('#shipping_settings #order_status').val(response['shipping_settings']['order_status']);
            	$('#shipping_settings #short_desc').val(response['shipping_settings']['short_desc']);
            	$('#shipping_settings #btn_settings').val('Update');

            } else {
            	$('#shipping_settings').modal();
            	$('#shipping_settings #store_id').val(store_id);
            	$('#shipping_settings #order_status, #shipping_settings #short_desc').val('');
            	$('#shipping_settings #btn_settings').val('Add');
            }
        },
        error: function()  {
            // alert(e.responseText);
        }

    });
}

$(document).ready(function(){
  	$("#btn_settings").click(function(){

  		store_id = $('#shipping_settings .modal-body #store_id').val();
  		order_status = $('#shipping_settings .modal-body #order_status').val();
  		short_desc = $('#shipping_settings .modal-body #short_desc').val();
  		
		$.ajax({
	        type: 'POST',
	        url: 'shippingsettings/update',
	        dataType: 'json',
	        data: {'_token': '{{ csrf_token() }}', 'store_id': store_id, 'order_status': order_status, 'short_desc': short_desc},
	        beforeSend: function () {
	        	$("#shipping_settings img").show();
	        },
	        complete: function () {
	        	$("#shipping_settings img").hide();
	        },
	        success: function (response) {  
	            if(response.response) { 	  
	            	$('#shipping_settings .alert-danger').hide();          	
	            	$('#shipping_settings .alert-success').text(response.successMessage).show();
	            } else {
	            	$('#shipping_settings .alert-success').hide();          	
	            	$('#shipping_settings .alert-danger').text(response.errorMessage).show();	            	
	            }
	        },
	        error: function()  {
	            // alert(e.responseText);
	        }

	    });

  	});
});

</script>

@stop