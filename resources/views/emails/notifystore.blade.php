<!DOCTYPE html>
<html>
<head>
	<title>Store Register Notification</title>
</head>
<body>	
	
	<h5 style="text-align: center;">Store Register Notification</h5>		
	<p> <b>See Your Dashboard </b> <a href="{{$dashboard_url}}">click here</a></p>
	<div>		
		<p> <b> Store Name: </b> {{ $name }} </p>
		<p> <b> Store url: </b> {{ $store_url }} </p>
		<p> <b> Technify Dashboard Password: </b> {{ $password }} </p>
		<p> <b> Email: </b> {{ $email }} </p>
		<p> <b> These Email's to Send order info: </b> {{ $support_email }} </p>
		<p> <b> License Key for connect to Technify: </b> {{ $uuid }} </p>
		<p> <b> Store Created Date: </b> {{ $created_at }} </p>
	</div>

</body>
</html>