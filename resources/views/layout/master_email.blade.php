<!DOCTYPE html>
<html lang="en">

<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">

	<title>
		@if (trim($__env->yieldContent('template_title')))@yield('template_title') - @endif Technify Shipping
	</title>

	@include('partials.email.head')

	<style type="text/css">
		@yield('cssscript')
	</style>

</head>

<body>

	@include('partials.email.nav')

	<div class="container">

		<div class="scroll-only-content">
        	@yield('content')        
        </div>		

	</div>  
</body>

</html>