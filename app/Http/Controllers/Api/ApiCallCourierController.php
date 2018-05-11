<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use Helper;

class ApiCallCourierController extends Controller
{
	// parsal shipped to Call Courier
    public function callCourier($payload) {
        
        file_put_contents('call_courier.json', json_encode($payload), true); 
        $call_courier = $payload['datapacket'];        

        $url = Config::get('urls.courier_urls.call_courier');
        $store_id   = $call_courier['orderinfo']['store_id'];
      	$store_spec = json_decode(Helper::getStoreSpec($store_id), true)['store_info'];
        $loginId    = $payload['credentials']['loginId'];
        $customer_name = $call_courier['customer']['firstname'].'-'.$call_courier['customer']['lastname'];
        $telephone = preg_replace("/[\s_]/", "+", $call_courier['customer']['telephone']);
        $order_id = $call_courier['orderinfo']['order_id'];  
        $address = preg_replace("/[\s_]/", "-", $call_courier['shipping_address']['address'].', '.$call_courier['shipping_address']['address_2']);
        $order_total_price = (int) Helper::getOrderTotalAmount($call_courier['total']);      
        $city = $call_courier['shipping_address']['city'];                
        $productDetails = $call_courier['cart'];                 
        $products = Helper::productDetails($productDetails);
        $qty = $products['qty'];        
        $weight = ($call_courier['weight'] && $call_courier['weight'] >= 0.5) ? $call_courier['weight'] : '0.5';
        $origin_city = $payload['origincity'];
        $desc = $products['name'];
        $destination_city = 18;
        $shipper_name  = preg_replace("/[\s_]/", "-", $store_spec['name']);
        $shipper_cell  = $store_spec['phone'];
        $shipper_addr  = preg_replace("/[\s#]/", "-", $store_spec['address']);
        $shipper_email = $store_spec['email'];
               
        $url = $url."?loginId=$loginId&ConsigneeName=$customer_name&ConsigneeRefNo=$order_id&ConsigneeCellNo=$telephone&Address=$address&Origin=$origin_city&DestCityId=$destination_city&ServiceTypeId=7&Pcs=$qty&Weight=$weight&Description=$desc&SelOrigin=Domestic&CodAmount=$order_total_price&SpecialHandling=false&MyBoxId=1%20My%20Box%20ID&Holiday=false&remarks=Remarks&ShipperName=$shipper_name&ShipperCellNo=$shipper_cell&ShipperArea=534&ShipperCity=2&ShipperAddress=$shipper_addr&ShipperLandLineNo=$shipper_cell&ShipperEmail=$shipper_email";                
        
        $response = Helper::curl($url, 'GET');
    
        return $response;
    }

    // get city list
    public function getCallCourierCityList($payload)
    {  
        $url = Config::get('urls.courier_urls.call_courier_city_list');
        return  Helper::curl($url, 'GET');
    }

    // cancel parsal 
    public function cancelShipmentCallcourier($payload)
    {
        $response = ['response' => false, 'errorMessage' => 'Call courier Cancel Api not integrated at Technify'];

        return $response;
    }

    // track parsal
    public function callcourierTrackParsal($payload)
    {
        $response = ['response' => false, 'errorMessage' => 'Call courier Track Parsal Api not integrated at Technify'];

        return $response;
    }
}
