@if( $errors->any() )
	<ul class="alert alert-danger col-md-8 col-md-offset-2">
		@foreach( $errors->all() as $error )
			<li style="margin-left: 5px;">{{ $error }}</li>
		@endforeach
	</ul>
@endif