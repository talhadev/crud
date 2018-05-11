@extends('layout.master')

@section('template_title')
    Manage Stores - {{$store->name}}
@endsection

<style type="text/css">
	.wrapper:hover .overlay, .wrapper img:hover{
		opacity: 0.8;
	}

	.overlay {
		position: absolute;
	  	opacity: 0;
	  	left: 45%;
	}
</style>

@section('content')	
	
	<div class="col-md-10 col-md-offset-1">

		<h3 class="text-muted text-center">Store | {{ $store->name }}</h3>
		<hr>
		<div class="panel-group">
		    <div class="panel panel-default">
			    <div class="panel-heading">{{ $store->name }}
			    	<span class="pull-right">  </span>
		    	</div>

			    <div class="panel-body">

			    	<div class="row pull-right">
					    <div class="col-md-12">    
					        <div class="col-md-12">

				        		<a href="/store" class="btn btn-warning"><span class="glyphicon glyphicon-arrow-left"></span> Back</a>	
							    <a href="{{$store->id}}/edit" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Edit</a>
							    <a href="javascript:;" class="btn btn-danger" data-toggle="modal" data-target="#dlt_store"> <span class="glyphicon glyphicon-trash"></span> Delete</a>

					        </div>
					    </div>
					</div>

					<div class="row">
					    <div class="col-md-12">    

					        <div class="col-md-6">				        	

								<a href="/logo.png" title="{{ $store->name }}" class="wrapper" target="_blank">
									<img class="img-circle img-responsive img-rounded img-thumbnail" src="/logo.png" width="100%" style="height: 400px;">
									<div class="overlay" style="top: 45%;">
										<img src="/more-icon.png">
									</div>
								</a>

					        </div>

					        <div class="col-md-6">

					        	<div class="row" data-toggle="collapse" href="#dept_inf">
						        	<div class="col-md-10">
						        		<h4>Store Information</h4> 					
					        		</div>
							        <div class="col-md-2">
							        	<br>
							        	<i class="glyphicon glyphicon-chevron-up"></i>
						        	</div>
				         		</div>

				         		<div id="dept_inf" class="collapse in">
						            <p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Store ID:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->id }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Store Name:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->name }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Email:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->email }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Store url:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->store_url }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Support Email:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->support_email }} </p> </div>
				            		</p>			            		
			            		
			            			<div class="clearfix"></div>
			            		</div>

			            		<div class="row" data-toggle="collapse" href="#security">
					         		<div class="col-md-10">
						        		<h4>Security</h4> 					
					        		</div>
							        <div class="col-md-2">
							        	<br>
							        	<i class="glyphicon glyphicon-chevron-down"></i>
						        	</div>
			            		</div>

			            		<div id="security" class="collapse">
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Uuid:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->uuid }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Password:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->password }} </p> </div>
				            		</p>
				            		<div class="clearfix"></div>
			            		</div>

			            		<div class="row" data-toggle="collapse" href="#date">
						        	<div class="col-md-10">
						        		<h4>Date</h4> 					
					        		</div>
							        <div class="col-md-2">
							        	<br>
							        	<i class="glyphicon glyphicon-chevron-up"></i>
						        	</div>
				         		</div>			         		

				         		<div id="date" class="collapse in">
						            <p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Start Date:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->created_at }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Updated Date:</b> </div>
					            		<div class="col-md-8"> <p> {{ $store->updated_at }} </p> </div>
				            		</p>
			            		
			            			<div class="clearfix"></div>
			            		</div>		            	

					        </div>

					    </div>
					</div>

			    </div>

			    <div class="panel-footer text-muted">
				     
			    </div>
		    </div>
		</div>	

		<!-- Modal for delete data -->
		<div id="dlt_dept" class="modal fade" role="dialog">
		  	<div class="modal-dialog">

			    <!-- Modal content-->
			    <div class="modal-content">
				    <div class="modal-header text-danger bg-danger">
				    	<button type="button" class="close" data-dismiss="modal">&times;</button>
				        <h4 class="modal-title">Delete Department</h4>
			        </div>
				    <div class="modal-body">
				        <p>Are you sure you want to Delete?</p>
				    </div>
				    <div class="modal-footer">
				        {!! Form::open(array('url' => 'store/' . $store->id)) !!}
		                    {!! Form::hidden('_method', 'DELETE') !!}
		                    {!! Form::submit('Yes', array('class' => 'btn btn-danger')) !!}
				        	<a href="javascript:;" class="btn btn-default" data-dismiss="modal">No</a>
		                {!! Form::close() !!}	
				    </div>
			    </div>

		  	</div>

		</div>	

	</div>
	<script type="text/javascript">

	    function toggleIcon(e) {
	    $(e.target)
	        .prev(this)
	        .find('.glyphicon')
	        .toggleClass('glyphicon-chevron-up glyphicon-chevron-down');
	        $(e.target).prev(this).find('p').toggle('show');
	    }
	    $(this).on('hidden.bs.collapse', toggleIcon);
	    $(this).on('shown.bs.collapse', toggleIcon);

	</script>

@stop