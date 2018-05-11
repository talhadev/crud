<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Leopard Sheet</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
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
    }

    .table > thead > tr > .no-line {
        border-bottom: none;
    }

    .table > tbody > tr > .thick-line {
        border-top: 0px solid;
    }

    #fixed {
        border:1px solid black;
        height:300px;
    }
</style>


<body>

<div class="container-fluid" style="border: 1px solid black;width:900px;">


    <div class="invoice-title">
        <img src="{{URL::asset('leopard_logo.png')}}" alt="">
        <h4 style="margin-left:60px;font-size:19px;font-weight:800;font-family:arial;color:black;">COD PARCEL</h4>
        <h4 style="font-weight:800;font-size:18px;font-family:arial;color:black;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(OVERNIGHT)</h4><img src="{{URL::asset('jahaz.png')}}" style="margin-top: -10px;" alt=""/>
        <h4 style="font-weight:800;font-size:18px;font-family:arial;color:black;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(LABEL)</h4>
        <h4 style="font-weight:800;font-size:18px;font-family:arial;color:black;margin-left: 55px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Handle with care</h4>


    </div>

    <div class="row" style=" padding:10px;">
        <div class="col-sm-2" style=" width:190px;">
            <h5 style="font-weight:600;margin-top: 3px; font-size:17px;font-family:arial;color:black;">Tracking No.</h5>
        </div>
        <div class="col-sm-2" style="">
            <a style="color:black;font-size: 17px;margin-left:20px; ">{{$trackingNumberShort}}</a>
        </div>
        <div class="col-sm-3" style="">
            <p style="color:black;margin-left: 10px;font-size: 17px;"><strong>ORDER ID</strong>&nbsp;&nbsp; {{$booked_packet_id}}</p>
        </div>
        <div class="col-sm-4" style="">
            <h5 style="margin-left: 50px;margin-top: -5px;"><img src="http://barcodes4.me/barcode/c39/{{$trackingNumber}}.png?width=300&height=60" ><br><p style="letter-spacing: 19px; font-size: 16px;margin-top: -5px;">{{$trackingNumber}}</p></h5>
        </div>

    </div>
    <div class="row" style="padding:10px;">
        <div class="col-sm-3" style="">
            <h5 style="font-weight: 600;font-size:17px; margin-top:-60px; font-family:arial;color:black;">Consignee Name</h5>
        </div>
        <div class="col-sm-2" style="margin-top:-65px;margin-left: -55px; ">
            <p style="color:black;font-size: 17px; ">{{$consignment_name_eng}}</p>
        </div>

    </div>
    <div class="row" style=" padding:10px;">
        <div class="col-sm-3" style=" width:200px;">
            <h5 style="margin-top:-40px;font-family:arial;font-weight: 600;font-size:17px;  color:black;">Consignee Address</h5>
        </div>
        <div class="col-sm-7" style="margin-left: -45px;margin-top:-6px;">
            <p style="border:2px solid grey;margin-top:-40px;color:black; margin-left: -10px;">{{$consignment_address}}</p>
        </div>

    </div>
    <div class="row" style=" padding:10px;">
        <div class="col-sm-2" style="width:190px;">
            <h5 style="margin-top:-30px;font-size:17px;font-family:arial;font-weight: 600; color:black;">Destination</h5>
        </div>
        <div class="col-sm-4" style="margin-left: 20px;">
            <p style="border:1px solid black;margin-top:-30px;color:black;font-size:17px;">{{$destination_city_name}}</p>
        </div>
        <div class="col-sm-3" style="">
            <h4 style="margin-top:-20px;font-size:17px;margin-left:20px;font-family:arial;font-weight: 600; color:black;">Weight</h4>
        </div>
        <div class="col-sm-2" style="">
            <p style="margin-top:-35px;color:black; margin-left:30px;letter-spacing: 1px;">{{$booked_packet_weight}}.00(grms)</p>
        </div>


    </div>
    <div class="row" style=" padding:10px;">
        <div class="col-sm-2" style="width:190px;">
            <h5 style="margin-top: -10px;font-size:17px;font-family:arial;font-weight: 600; color:black;">Contact Nos</h5>
        </div>
        <div class="col-sm-4" style="margin-left: 20px;margin-top: -5px;">
            <p style="border:1px solid black;margin-top: -10px;font-size:17px;color:black;">{{$consignment_phone}}</p>
        </div>
        <div class="col-sm-4" style="">
            <p style="color:black;margin-left:20px;margin-top: -10px;font-size:17px;font-family:arial;font-weight: 600; color:black;">Cash Collection Amount</p>
        </div>
        <div class="col-sm-2" style="margin-left: -50px;">
            <h5 style="margin-top: -45px;margin-left: -10px;"><img src="http://barcodes4.me/barcode/c39/{{$booked_packet_collect_amount}}.png?width=155&height=70" ><br><p style="font-weight:600;font-size:19px;letter-spacing: 1px; width:160px;color:black;margin-left: 20px; margin-top: -5px; ">PKR{{$booked_packet_collect_amount}}.00</p></h5>
        </div>

    </div>
    <div class="row" style=" padding:10px;margin-top:-30px;">
        <div class="col-sm-2" style="width:190px;">
            <h5 style="font-size:17px;font-family:arial;font-weight: 600; color:black;">Remarks</h5>
        </div>
        <div class="col-sm-2" style="margin-top:-5px;margin-left:19px;">
            <a style="color:black; font-size:17px;">{{$special_instructions}}</a>
        </div>

    </div>
    <div class="row" style=" padding:10px; margin-top: -10px;">
        <div class="col-sm-3" style="width:200px;">
            <h5 style="font-size:17px;font-family:arial;font-weight: 600; color:black;">Shipper AC / Name</h5>
        </div>
        <div class="col-sm-4" style="margin-left:-50px; margin-top: -5px;">
            <p style="color:black;font-size: 17px;">541117 /{{$shipment_name_eng}}</p>
        </div>
        <div class="col-sm-5">
            <h5 style="color:black;margin-left:150px;font-size:15px;"><strong>Date</strong>&nbsp; {{$booking_date}}</h5>
        </div>


    </div>
    <div class="row" style=" padding:10px;margin-top: -25px;">
        <div class="col-sm-3" style="width:190px;">
            <h5 style="font-family:arial;font-size:17px;font-weight: 600; color:black;">Shipper Address</h5>
        </div>
        <div class="col-sm-9" style="margin-left: -50px;margin-top:-5px;">
            <p style="border:1px solid black;color:black;font-size: 17px;">{{$shipment_address}}</p>
        </div>

    </div>
    <div class="row" style=" padding:10px;margin-top: -20px;">
        <div class="col-sm-3" style="width:190px;">
            <p style="margin-top: -20px;font-size:17px;color:black;"> UAN: 111 300 786</p>
        </div>
        <div class="col-sm-7" style="float:right;margin-left: 70px;">
            <p style="margin-top: -20px; color:black;float:right;font-size:17px;">Website: http://www.leopardscourier.com</p>
        </div>

    </div>




</div>
</body>