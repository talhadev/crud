<html>
<head>
    <meta charset="utf-8">
    <title>Tcs</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<header>
</header>
    <title>Items sold summary</title>
    <style>
        table {
            border-collapse: collapse;
            border-bottom: 0.8px solid black;
            border-top: 0.8px solid black;
            border-left: 0.8px solid black;
            border-right: 0.8px solid black;
            letter-spacing: 1px;
            font-size: 0.8rem;
        }
        td, th {
            border: 1px solid black;
            padding: 10px 20px;
            text-align: left;
            font-family: Arial;
        }
        th {
            background-color: white;
        }
        td {
            text-align: left;
            margin-left: -50px;
        }
        tr:nth-child(even) td {
            background-color: white;
        }
        tr:nth-child(odd) td {
            background-color: white;
        }
        caption {
            padding: 2px;
        }
        tbody {
            font-size: 15px;
            font-style: normal;
            font-family: Arial;
        }
        tfoot {
            font-weight: bold;
        }
        .consigneeBox{
            border: 1px solid black; height: 30px;  border-bottom:1px solid black; width:149px; margin-top: -55px;margin-left: -20px;
        }
    </style>

<body>
<table style="height: 100px;">
    <tr>
        <td rowspan='3' colspan='1'><img src="{{URL::asset('tcs_logo.png')}}" alt=""/></td>
        <td rowspan='3' colspan='2'><?php  echo DNS1D::getBarcodeSVG($CN, "C39+",0.7,50);?><br><a style="margin-left:40px; font-size:12px; ">{{$CN}}</a><br><a style="margin-left: 6px; font-family: Cambria; font-size:12px; ">Consignee copy</a></td>
        <td colspan='3' >Date</td>
        <td colspan='3' >18/08/2017</td>
        <td colspan='3'>Time</td>
        <td colspan='4'  >14:41</td>
    </tr>
    <tr>
        <td colspan='3' >Service</td>
        <td colspan='3' >{{$Service}}</td>
        <td colspan='3' >&nbsp;</td>
        <td colspan='4' ></td>
    </tr>
    <!-- Third row -->
    <tr>
        <td colspan='3'  >Origin</td>
        <td colspan='3' style='font-style: bold;'>{{$Origin}}</td>
        <td colspan='3' >Destination</td>
        <td colspan='4' >{{$Destination}}</td>
    </tr>
    <!-- Fourth row -->
    <tr style='height:110px; boder-bottom:2px solid grey;' >
        <td colspan='6'>
            <div style='border: 1px solid black; border-bottom: 2px solid grey; height: 30px; width:154px; margin-top: -55px;margin-left: -20px;'>Shipper</div>
            <div style=' width:200px; height:30px; margin-left: 150px; margin-top: -20px;'>M2 (PRIVATE) LIMITEd</div>
            <div style=' height:0px;width:280px; margin-top: 10px;'>R-880, SECTOR -33E KORANGI 2-1/2, LABOUR COLONY,
                KORANGI INDUSTRIAL AREA. KHI
                KH</div>
        </td>
        <td colspan='10' style='border-right:2px solid grey; '>
            <div class='consigneeBox' >Consignee</div>
            <div style=' width:250px;height:30px; margin-left: 140px; margin-top: -20px;'>{{$Consignee}}</div>
            <div style=' height:0px;width:280px; margin-top: 10px;'>{{$ConsigneeAddress}}</div>
        </td>
    </tr>
    <tr style='border-top:2px solid grey;'>
        <td colspan='1.5' style='font-weight: bold;'>Pieces</td>
        <td colspan='1'>{{$ShipmentPieces}}</td>
        <td colspan='1.5'  style='font-weight: bold;'>Weight</td>
        <td colspan='1'>{{$ShipmentWeight}}</td>
        <td colspan='1.5'  style='font-weight: bold;'>Fragile</td>
        <td colspan='3'>{{$Fragile}}</td>
        <td colspan='3'>&nbsp;</td>
        <td colspan='5'>&nbsp;</td>
    </tr>
    <tr>
        <td colspan='4'>Declared insurance value</td>
        <td colspan='2'>{{$InsuranceValue}}</td>
        <td colspan='3'  style='font-weight: bold;'>COD AMOUNT</td>
        <td colspan='7'  style='font-weight: bold;'>{{$CODAmount}}/-</td>
    </tr>
    <tr>
        <td colspan='3'  style='font-weight: bold;'>Product Detail</td>
        <td colspan='16'  style='font-weight: bold;'>{{$ProductDetail}}</td>
    </tr>
    <tr>
        <td colspan='3'  style='font-weight: bold;'>Remarks</td>
        <td  colspan='16'>{{$Remarks}}</td>
    </tr>
    <tr style='border-top:2px solid grey;'>
        <td colspan='3' >CustRef#</td>
        <td  colspan='12'>{{$CustomerRef}}</td>
        <td colspan='3'>&nbsp;</td>
    </tr>
    <tr>
        <td colspan='18'  style='font-weight: bold;'> Please don't accept, if shipment is not intact. Before paying the COD, shipment can not be open.</td>
    </tr>
</table>
 <a>-----------------------------------------------------------------------------------------------------------------------------</a>
<table>
    <tr>
        <td rowspan='3' colspan='1'><img src="{{URL::asset('tcs_logo.png')}}" alt=""/></td>
        <td rowspan='3' colspan='2'><?php  echo DNS1D::getBarcodeSVG($CN, "C39+",0.7,50);?><br><a style="margin-left:40px; font-size:12px; ">{{$CN}}</a><br><a style="margin-left: 6px; font-family: Cambria; font-size:12px; ">Shipper's copy</a></td>
        <td colspan='3' >Date</td>
        <td colspan='3' >18/08/2017</td>
        <td colspan='3'>Time</td>
        <td colspan='4'  >14:41</td>
    </tr>
    <tr>
        <td colspan='3' >Service</td>
        <td colspan='3' >{{$Service}}</td>
        <td colspan='3' >&nbsp;</td>
        <td colspan='4' ></td>
    </tr>
    <!-- Third row -->
    <tr>
        <td colspan='3'  >Origin</td>
        <td colspan='3' style='font-style: bold;'>{{$Origin}}</td>
        <td colspan='3' >Destination</td>
        <td colspan='4' >{{$Destination}}</td>
    </tr>
    <!-- Fourth row -->
    <tr style='height:110px; boder-bottom:2px solid grey;' >
        <td colspan='6'>
            <div style='border: 1px solid black; border-bottom: 2px solid grey; height: 30px; width:154px; margin-top: -55px;margin-left: -20px;'>Shipper</div>
            <div style=' width:200px; height:30px; margin-left: 150px; margin-top: -20px;'>M2 (PRIVATE) LIMITEd</div>
            <div style=' height:0px;width:280px; margin-top: 10px;'>R-880, SECTOR -33E KORANGI 2-1/2, LABOUR COLONY,
                KORANGI INDUSTRIAL AREA. KHI
                KH</div>
        </td>
        <td colspan='10' style='border-right:2px solid grey; '>
            <div class='consigneeBox' >Consignee</div>
            <div style=' width:250px;height:30px; margin-left: 140px; margin-top: -20px;'>{{$Consignee}}</div>
            <div style=' height:0px;width:280px; margin-top: 10px;'>{{$ConsigneeAddress}}</div>
        </td>
    </tr>
    <tr style='border-top:2px solid grey;'>
        <td colspan='1.5' style='font-weight: bold;'>Pieces</td>
        <td colspan='2.5'>{{$ShipmentPieces}}</td>
        <td colspan='1.5'  style='font-weight: bold;'>Weight</td>
        <td colspan='2'>{{$ShipmentWeight}}</td>
        <td colspan='3'  style='font-weight: bold;'>Fragile</td>
        <td colspan='3'>{{$Fragile}}</td>
        <td colspan='3'>&nbsp;</td>
        <td colspan='4'>&nbsp;</td>
    </tr>
    <tr>
        <td colspan='6'>Declared insurance value</td>
        <td colspan='3'>{{$InsuranceValue}}</td>
        <td colspan='3'  style='font-weight: bold;'>COD AMOUNT</td>
        <td colspan='7'  style='font-weight: bold;'>{{$CODAmount}}/-</td>
    </tr>
    <tr>
        <td colspan='3'  style='font-weight: bold;'>Product Detail</td>
        <td colspan='16'  style='font-weight: bold;'>{{$ProductDetail}}</td>
    </tr>
    <tr>
        <td colspan='3'  style='font-weight: bold;'>Remarks</td>
        <td  colspan='16'>{{$Remarks}}</td>
    </tr>
    <tr style='border-top:1px solid grey;'>
        <td colspan='3' >CustRef#</td>
        <td  colspan='12'>{{$CustomerRef}}</td>
        <td colspan='3'>&nbsp;</td>
    </tr>
    <tr>
        <td colspan='18'  style='font-weight: bold;'> Please don't accept, if shipment is not intact. Before paying the COD, shipment can not be open.</td>
    </tr>
</table>
<a>----------------------------------------------------------------------------------------------------------------------------</a>
<table>
    <tr>
        <td rowspan='3' colspan='1'><img src="{{URL::asset('tcs_logo.png')}}" alt=""/></td>
        <td rowspan='3' colspan='2'><?php  echo DNS1D::getBarcodeSVG($CN, "C39+",0.7,50);?><br><a style="margin-left:40px; font-size:12px; ">{{$CN}}</a><br><a style="margin-left: 6px; font-family: Cambria; font-size:12px; ">Consignee copy</a></td>
        <td colspan='3' >Date</td>
        <td colspan='3' >18/09/2017</td>
        <td colspan='3'>Time</td>
        <td colspan='4'  >14:41</td>
    </tr>
    <tr>
        <td colspan='3' >Service</td>
        <td colspan='3' >{{$Service}}</td>
        <td colspan='3' >&nbsp;</td>
        <td colspan='4' ></td>
    </tr>
    <!-- Third row -->
    <tr>
        <td colspan='3'  >Origin</td>
        <td colspan='3' style='font-style:bold;'>{{$Origin}}</td>
        <td colspan='3' >Destination</td>
        <td colspan='4' >{{$Destination}}</td>
    </tr>
    <!-- Fourth row -->
    <tr style='height:110px; boder-bottom:1px solid grey;' >
        <td colspan='6'>
            <div style='border: 1px solid black; border-bottom: 1px solid black; height: 30px; width:154px; margin-top: -55px;margin-left: -20px;'>Shipper</div>
            <div style=' width:200px; height:30px; margin-left: 150px; margin-top: -20px;'>M2 (PRIVATE) LIMITEd</div>
            <div style=' height:0px;width:280px; margin-top: 10px;'>R-880, SECTOR -33E KORANGI 2-1/2, LABOUR COLONY,
                KORANGI INDUSTRIAL AREA. KHI
                KH</div>
        </td>
        <td colspan='10' style='border-right:1px solid black; '>
            <div class='consigneeBox' >Consignee</div>
            <div style=' width:250px;height:30px; margin-left: 140px; margin-top: -20px;'>{{$Consignee}}</div>
            <div style=' height:0px;width:280px; margin-top: 10px;'>{{$ConsigneeAddress}}</div>
        </td>
    </tr>
    <tr style='border-top:2px solid grey;'>
        <td colspan='1.5' style='font-weight: bold;'>Pieces</td>
        <td colspan='2.5'>{{$ShipmentPieces}}</td>
        <td colspan='1.5'  style='font-weight: bold;'>Weight</td>
        <td colspan='2'>{{$ShipmentWeight}}</td>
        <td colspan='3'  style='font-weight: bold;'>Fragile</td>
        <td colspan='3'>{{$Fragile}}</td>
        <td colspan='3'>&nbsp;</td>
        <td colspan='4'>&nbsp;</td>
    </tr>
    <tr>
        <td colspan='6'>Declared insurance value</td>
        <td colspan='3'>{{$InsuranceValue}}</td>
        <td colspan='3'  style='font-weight: bold;'>COD AMOUNT</td>
        <td colspan='7'  style='font-weight: bold;'>{{$CODAmount}}/-</td>
    </tr>
    <tr>
        <td colspan='3'  style='font-weight: bold;'>Product Detail</td>
        <td colspan='16'  style='font-weight: bold;'>{{$ProductDetail}}</td>
    </tr>
    <tr>
        <td colspan='3'  style='font-weight: bold;'>Remarks</td>
        <td  colspan='16'>{{$Remarks}}</td>
    </tr>
    <tr style='border-top:2px solid grey;'>
        <td colspan='3' >CustRef#</td>
        <td  colspan='12'>{{$CustomerRef}}</td>
        <td colspan='3'>&nbsp;</td>
    </tr>
    <tr>
        <td colspan='18'  style='font-weight: bold;'> Please don't accept, if shipment is not intact. Before paying the COD, shipment can not be open.</td>
    </tr>
</table>
</body>
</html>