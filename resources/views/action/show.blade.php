@extends('layout.master')

@section('template_title')
    Manage Action - {{$action->method}}
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

		<h3 class="text-muted text-center">Action | {{ $action->method }}</h3>
		<hr>
		<div class="panel-group">
		    <div class="panel panel-default">
			    <div class="panel-heading">{{ $action->method }}
			    	<span class="pull-right">  </span>
		    	</div>

			    <div class="panel-body">

			    	<div class="row pull-right">
					    <div class="col-md-12">    
					        <div class="col-md-12">

				        		<a href="/actions" class="btn btn-warning"><span class="glyphicon glyphicon-arrow-left"></span> Back</a>	
							    <a href="{{$action->id}}/edit" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Edit</a>
							    <a href="javascript:;" class="btn btn-danger" data-toggle="modal" data-target="#dlt_action"> <span class="glyphicon glyphicon-trash"></span> Delete</a>

					        </div>
					    </div>
					</div>

					<div class="row">
					    <div class="col-md-12">    

					        <div class="col-md-6">				        	

								<a href="/logo.png" title="{{ $action->method }}" class="wrapper" target="_blank">
									<img class="img-circle img-responsive img-rounded img-thumbnail" src="/logo.png" width="100%" style="height: 400px;">
									<div class="overlay" style="top: 45%;">
										<img src="/more-icon.png">
									</div>
								</a>

					        </div>

					        <div class="col-md-6">

					        	<div class="row" data-toggle="collapse" href="#action">
						        	<div class="col-md-10">
						        		<h4>Action Information</h4> 					
					        		</div>
							        <div class="col-md-2">
							        	<br>
							        	<i class="glyphicon glyphicon-chevron-up"></i>
						        	</div>
				         		</div>

				         		<div id="action" class="collapse in">
						            <p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Controller:</b> </div>
					            		<div class="col-md-8"> <p> {{ $action->controller }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Method:</b> </div>
					            		<div class="col-md-8"> <p> {{ $action->method }} </p> </div>
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
					            		<div class="col-md-8"> <p> {{ $action->created_at }} </p> </div>
				            		</p>
				            		<p class="text-muted"> 
						            	<div class="col-md-4"> <b class="text-muted">Updated Date:</b> </div>
					            		<div class="col-md-8"> <p> {{ $action->updated_at }} </p> </div>
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
				        {!! Form::open(array('url' => 'actions/' . $action->id)) !!}
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