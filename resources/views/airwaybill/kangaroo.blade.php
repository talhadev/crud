<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Kangaroo Logistics Order Sheet</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<header>
</header>

<style>

    .invoice-title h2, .invoice-title h3, .invoice-title h4, .invoice-title p {
        display: inline-block;
    }

    .table > tbody > tr > .no-line {
        border-top: none;

        border-bottom: none;
    }

    .table > thead > tr > .no-line {
    .table > tbody > tr > .thick-line {
        border-top: 2px solid;
    }

    #fixed {
        border:1px dashed black;
        height:300px;
    }
</style>


<body>
<div class="container">
    <div class="row" style=" border:1px dashed black;" id="fixed">
        <div class="col-xs-12">
            <div class="invoice-title">
                <img src="{{URL::asset('kangaroo.png')}}" width="100" height="100">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<h4>Kangaroo Copy</h4><h4 class="pull-right">Order # {{$order_id}}</h4>
                <p>(Cash On delivery)</p>
                <img src="http://barcodes4.me/barcode/c39/{{$order_id}}.png?width=100&height=40" style="margin-left:20px;"><br>
                <p class="pull-right">2017-09-22 ( karachi ) </p>
            </div>
            <hr>
            <div class="row">
                <div class="col-xs-6">
                    <address>
                        <strong>Shipped To:</strong><br>{{$Customername}}<br>
                        {{$Customeraddress}}  <br>
                        {{$Customernumber}}<br>
                        {{$City}}<br>
                        {{$Amount}}    				</address>
                </div>
                <div class="col-xs-6 text-right">
                    <address>

                        {{$Clientname}}<br>
                        {{$Invoice}}<br>
                        {{$Productname}}<br>
                        {{$Productcode}}<br>
                        {{$Clientaddress}}<br>
                        {{$Clientphone}}    				</address>
                </div>
            </div>

        </div>
    </div>

    <div class="row" style=" border:1px dashed black;" id="fixed">
        <div class="col-xs-12">
            <div class="invoice-title">
                <img src="{{URL::asset('kangaroo.png')}}" width="100" height="100">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<h4>Kangaroo Copy</h4><h4 class="pull-right">Order # {{$order_id}}</h4>
                <p>(Cash On delivery)</p>
                <img src="http://barcodes4.me/barcode/c39/{{$order_id}}.png?width=100&height=40" style="margin-left:20px;"><br>
                <p class="pull-right">2017-09-22 ( karachi ) </p>
            </div>
            <hr>
            <div class="row">
                <div class="col-xs-6">
                    <address>
                        <strong>Shipped To:</strong><br>{{$Customername}}<br>
                        {{$Customeraddress}}  <br>
                        {{$Customernumber}}<br>
                        {{$City}}<br>
                        {{$Amount}}    				</address>
                </div>
                <div class="col-xs-6 text-right">
                    <address>

                        {{$Clientname}}<br>
                        {{$Invoice}}<br>
                        {{$Productname}}<br>
                        {{$Productcode}}<br>
                        {{$Clientaddress}}<br>
                        {{$Clientphone}}    				</address>
                </div>
            </div>

        </div>
    </div>

    <div class="row" style=" border:1px dashed black;" id="fixed">
        <div class="col-xs-12">
            <div class="invoice-title">
                <img src="{{URL::asset('kangaroo.png')}}" width="100" height="100">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<h4>Kangaroo Copy</h4><h4 class="pull-right">Order # {{$order_id}}</h4>
                <p>(Cash On delivery)</p>
                <img src="http://barcodes4.me/barcode/c39/{{$order_id}}.png?width=100&height=40" style="margin-left:20px;"><br>
                <p class="pull-right">2017-09-22 ( karachi ) </p>
            </div>
            <hr>
            <div class="row">
                <div class="col-xs-6">
                    <address>
                        <strong>Shipped To:</strong><br>{{$Customername}}<br>
                        {{$Customeraddress}}  <br>
                        {{$Customernumber}}<br>
                        {{$City}}<br>
                        {{$Amount}}    				</address>
                </div>
                <div class="col-xs-6 text-right">
                    <address>

                        {{$Clientname}}<br>
                        {{$Invoice}}<br>
                        {{$Productname}}<br>
                        {{$Productcode}}<br>
                        {{$Clientaddress}}<br>
                        {{$Clientphone}}    				</address>
                </div>
            </div>

        </div>
    </div>

</div>
</body>