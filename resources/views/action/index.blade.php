@extends('layout.master')

@section('template_title')
    Manage Actions
@endsection

@section('content')
	<?php $counter = 1; ?>
	
	<h3 class="text-muted text-center">Actions</h3>

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
	
    {!! Form::open(['url' => '/action/filter']) !!}

		@include('action.filterform', ['sumbitButtonText' => 'Filter'])

	{!! Form::close() !!}
	
	@if( count($actions) > 0 )	
		<table class="table table-striped table-hover table-responsive table-condensed table-layout">
		<tr>
			<th>S NO 		  	</th>
			<th>Controller Name </th>
			<th>Action          </th>
			<th>Call            </th>
			<th>Method Name 	</th>
			<th>Edit 	  	  	</th>
		</tr>
		@foreach( $actions as $action)			

			<tr>
				<td>{{ $counter }}		        </td>
				<td>{{ $action->controller }}  	</td>
				<td>{{ $action->action }}  	</td>
				<td>{{ ($action->call == 1) ? 'internal' : 'external' }} </td>
				<td>{{ $action->method }}  		</td>							
				<td>
					<a href="/actions/{{$action->id}}/edit" title="edit action" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span></a>
	                <a href="javascript:;" class="btn btn-danger" title="delete action" data-toggle="modal" data-target="#dlt_action-{{$action->id}}"><i class="glyphicon glyphicon-trash"></i></a>
				</td>
			</tr>

			<!-- Modal for delete data -->
			<div id="dlt_action-{{$action->id}}" class="modal fade" role="dialog">
			  	<div class="modal-dialog">				    
				    <div class="modal-content">
					    <div class="modal-header text-danger bg-danger">
					    	<button type="button" class="close" data-dismiss="modal">&times;</button>
					        <h4 class="modal-title">Delete Action</h4>
				        </div>
					    <div class="modal-body">
					        <p>Are you sure you want to Delete?</p>
					    </div>
					    <div class="modal-footer">
					        {!! Form::open(['url' => 'actions/' . $action->id]) !!}
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
		</table>	

		<!-- pagination -->
		@if( $actions->render() )
			<div class="pull-right">
				<div class="text-muted">
					Showing {{ $actions->toArray()['from'] }} - {{ $actions->toArray()['to'] }} of {{ $actions->toArray()['total'] }}
				</div>
			</div>

			<div class="text-center">
				<div class="pagination">{!! $actions->render() !!}</div>
			</div>
			<div class="clearfix"></div>
		@endif	

	@else
		<p class="text-muted">No action yet</p>
	@endif

	<a href="/actions/create" class="btn btn-link">create new action</a>		

@stop