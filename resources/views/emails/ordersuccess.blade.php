<!DOCTYPE html>
<html>
<head>
	<title>Order Success</title>
</head>
<body>

	<h5 style="text-align: center;">Order Success</h5>
	
	<p>Your order<b> # {{$order_id}}</b> is successfully placed with {{$courier_company}}. Tracking id is <b>{{$tracking_id}}</b>. Please visit this link to track you order <a href="{{$courier_url}}"> click here </a> .</p>	

	See complete order success list here <a href="{{\Config::get('urls.navigation_urls.technify_dashboard')}}">Order Success</a>

	For feedback and complaints, email us on <b>info@technify.pk</b>

</body>
</html>