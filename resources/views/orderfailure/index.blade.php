@extends('layout.master')

@section('template_title')
    Order List
@endsection

@section('content')
	<?php $sucs_counter = 1; $fail_counter = 1; $cncl_counter = 1; ?>
	
	<h3 class="text-muted text-center">Orders</h3>

	@if(Session::has('flash_message'))
        <div class="alert alert-success">
            <strong>Success!</strong> {{ Session::get('flash_message') }}
        </div>
    @endif    

    @if(Session::has('error_message'))
        <div class="alert alert-danger">
            <strong>Warning!</strong> {{ Session::get('error_message') }}
        </div>
    @endif   

    <div class="alert alert-danger unselect_order_alert" style="display: none;">
    	<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    	<span></span>
    </div>

	<ul class="nav nav-tabs">
		<li><a data-toggle="tab" href="#success"><span class="text-success">Success Orders</span></a></li>
		<li><a data-toggle="tab" href="#cancelled"><span class="text-warning">Cancelled Orders</span></a></li>
		<li class="active"><a data-toggle="tab" href="#failure"><span class="text-danger">Failure Orders</span></a></li>
	</ul>	

    <div class="tab-content">
    	<div id="success" class="tab-pane fade">
			<div class="col-md-12">			
				<h3 class="text-muted text-center">Success Orders</h3>
				<hr/>
				@if( count($order_success) > 0 )	
					<table class="table table-striped table-hover table-responsive table-condensed table-layout">
					<tr>
						<th>S NO 		  	   </th>
						<th>Store 		  	   </th>
						<th>Order ID 	  	   </th>
						<th>Courier Company    </th>
						<th>Order Tracking ID  </th>
						<th>Status 		   	   </th>
						<th>Created at 		   </th>
					</tr>
					@foreach( $order_success as $order_suces )			

						<tr>
							<td>{{ $sucs_counter }}					  </td>
							<td>{{ ($store->name) ? $store->name : N/A }} </td>
							<td>{{ $order_suces->order_id }}  		  </td>
							<td>{{ $order_suces->courier_name }}  	  </td>
							@if( $order_suces->courier_name == 'kangaroo' )
							<td>
								<a href="http://kangaroo.retailogics.pk/clienteditorder.php?id={{ $order_suces->order_tracking_id }}" target="_blank">{{ $order_suces->order_tracking_id }} </a>
							</td>
							@else
							<td>
								<a href="http://webapp.tcscourier.com/COD/POPUPTracking.aspx?trackNo={{ $order_suces->order_tracking_id }}" target="_blank">{{ $order_suces->order_tracking_id }} </a>
							</td>
							@endif
							<td>{{ ($order_suces->status == '1') ? 'success' : '' }}	</td>
							<td>{{ $order_suces->created_at }}		  </td>				
						</tr>

						<?php $sucs_counter+=1; ?>
					@endforeach
					</table>

					<!-- pagination -->
					@if( $order_success->render() )

						<div class="pull-right">
							<div class="text-muted">
								Showing {{ $order_success->toArray()['from'] }} - {{ $order_success->toArray()['to'] }} of {{ $order_success->toArray()['total'] }}
							</div>
						</div>

						<div class="text-center">
							<div class="pagination">{!! $order_success->render() !!}</div>
						</div>
						<div class="clearfix"></div>
					@endif		

				@else
					<p class="text-muted">No Success order yet</p>
				@endif	
			</div>
		</div>

		<div id="failure" class="tab-pane fade in active">
			<div class="col-md-12">			
				<h3 class="text-muted text-center">Failure Orders</h3>
				<hr/>
				@if( count($order_failure) > 0 )	

					<div class="checkbox">
						<label class="btn btn-link"><input type="checkbox" value="" id="check_all_fail_orders">Check all</label>		
				    </div>

					<table class="table table-striped table-hover table-responsive table-condensed table-layout">
					<tr>
						<th>S NO 	   </th>
						<th>Store 	   </th>
						<th>Order ID   </th>
						<th>Address    </th>
						<th>City 	   </th>
						<th>Telephone  </th>
						<th>Email 	   </th>
						<th>Status 	   </th>
						<th>Created at </th>						
						<th>Action     </th>
					</tr>

					{!! Form::open(['url' => '/order/failure/proceed', 'id' => 'order-proceed-form']) !!}

					@foreach( $order_failure as $order_fail )			

						<tr>
							<td>								
								<div class="checkbox text-center">
									<label>
										<input type="checkbox" class="select_order" name="{{$order_fail->id}}" value="{{$order_fail->id}}">
										{{ $fail_counter }}	
									</label>
							    </div>						    	
							</td>
							<td>{{ $store->name }}			  		  </td>
							<td>{{ $order_fail->order_id }}  		  </td>
							<td>{{ ($order_fail->failure_address) ? $order_fail->failure_address : 'N/A' }} </td>
							<td>{{ ($order_fail->failure_city) ? $order_fail->failure_city : 'N/A' }}    	</td>
							<td>{{ ($order_fail->telephone) ? $order_fail->telephone    : 'N/A' }}    	</td>
							<td>{{ ($order_fail->email) ? $order_fail->email    : 'N/A' }}    	</td>
							<td>{{ ($order_fail->status == '1') ? '' : 'failure' }}					</td>
							<td>{{ $order_fail->created_at }}		  </td>											
							<td>
								<a href="/order/failure/{{$order_fail->id}}/edit" title="show complete info" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span></a>
							</td>
						</tr>

						<?php $fail_counter+=1; ?>
					@endforeach

					{!! Form::close() !!}
					</table>												

					<div class="pull-right">
						@if($data['show_change_status'])
							<a href="javascript:;" onclick="changeStatus()" class="btn btn-primary" {{ ($data['disable_change_status']) ? 'disabled' : '' }}>Change status</a>
						@endif
						@if($data['show_proceed'])
						<a href="javascript:;" onclick="proceed()" class="btn btn-primary" {{ ($data['disable_proceed']) ? 'disabled' : '' }}>Proceed</a>
						@endif
					</div>
					<div class="clearfix"></div>

					<!-- pagination -->
					@if( $order_failure->render() )
						
						<div class="text-muted">
							Showing {{ $order_failure->toArray()['from'] }} - {{ $order_failure->toArray()['to'] }} of {{ $order_failure->toArray()['total'] }}
						</div>

						<div class="text-center">
							<div class="pagination">{!! $order_failure->render() !!}</div>
						</div>
						<div class="clearfix"></div>
					@endif				

				@else
					<p class="text-muted">No Failure order yet</p>
				@endif	
			</div>
		</div>

		<div id="cancelled" class="tab-pane fade">
			<div class="col-md-12">			
				<h3 class="text-muted text-center">Cancelled Orders</h3>
				<hr/>
				@if( count($order_cancelled) > 0 )	
					<table class="table table-striped table-hover table-responsive table-condensed table-layout">
					<tr>
						<th>S NO 		  	   </th>
						<th>Store 		  	   </th>
						<th>Order ID 	  	   </th>
						<th>Courier Company    </th>
						<th>Order Tracking ID  </th>
						<th>Status 		   	   </th>
						<th>Created at 		   </th>
					</tr>
					@foreach( $order_cancelled as $order_cancel )			

						<tr>
							<td>{{ $cncl_counter }}					  </td>
							<td>{{ ($store->name) ? $store->name : N/A }} </td>
							<td>{{ $order_cancel->order_id }}  		  </td>
							<td>{{ $order_cancel->courier_name }}  	  </td>
							@if( $order_cancel->courier_name == 'kangaroo' )
							<td><a href="http://kangaroo.retailogics.pk/clienteditorder.php?id={{ $order_cancel->order_tracking_id }}">{{ $order_cancel->order_tracking_id }} </a></td>
							@else
							<td>{{ $order_cancel->order_tracking_id }}</td>
							@endif
							<td>{{ ($order_cancel->status == '2') ? 'cancelled' : '' }}	</td>
							<td>{{ $order_cancel->created_at }}		  </td>				
						</tr>

						<?php $cncl_counter+=1; ?>
					@endforeach
					</table>

					<!-- pagination -->
					@if( $order_cancelled->render() )
						<div class="pull-right">
							<div class="text-muted">
								Showing {{ $order_cancelled->toArray()['from'] }} - {{ $order_cancelled->toArray()['to'] }} of {{ $order_cancelled->toArray()['total'] }}
							</div>
						</div>

						<div class="text-center">
							<div class="pagination">{!! $order_cancelled->render() !!}</div>
						</div>
						<div class="clearfix"></div>
					@endif		

				@else
					<p class="text-muted">No Cancelled order yet</p>
				@endif	
			</div>
		</div>
	</div>
	
	<!-- Modal change status confirmation -->
	<div class="modal fade" id="confirmChangeStatus" role="dialog">
		<div class="modal-dialog modal-md">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Confimation Change Status / remove order</h4>
				</div>
				<div class="modal-body">
					<p>Are you sure change selected status / remove failure order ?</p>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn btn-danger change-failure-status">Yes</a>
					<button type="button" class="btn btn-default" data-dismiss="modal">No</button>
				</div>
			</div>
		</div>
	</div>

<script type="text/javascript">
	$(document).ready(function(){
		$('#check_all_fail_orders').change(function () {    
			if(this.checked){
				$('.select_order').prop("checked", true); 
			}
			else{
				$('.select_order').prop("checked", false);
			}
		});
	});

	function proceed() {
		if($('.select_order').is(':checked')) {			
			$('#order-proceed-form').submit();
		} else {	
			$('.unselect_order_alert').show('5').find('span').html('<strong>Warning!</strong> please select order');			
		}
	}

	function changeStatus() {
		if($('.select_order').is(':checked')) {
			$('#confirmChangeStatus').modal('show'); 
			$('.unselect_order_alert').hide('5');
		}
		else {			
			$('.unselect_order_alert').show('5').find('span').html('<strong>Warning!</strong> please select order');			
		}
	}

	$(".change-failure-status").on("click", function(e){
	    e.preventDefault();
	    $('#order-proceed-form').attr('action', "/order/failure/change/status").submit();
	});

	$(document).ready(function(){
		var getUrlSegemnt = '{{Request::segment(2)}}'; 
		if(getUrlSegemnt) {			
			$('.nav-tabs a[href="#' + getUrlSegemnt + '"]').tab('show');
		}
	});
</script>

@stop