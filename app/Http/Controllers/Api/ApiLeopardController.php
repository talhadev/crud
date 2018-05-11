<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

// Models 
use App\Models\Shipping;

class ApiLeopardController extends Controller
{
    // LEAOPARD 
    public function leopard($payload) {   
	  	  
      	file_put_contents('leopard.json', json_encode($payload, true));   

        $action     = $payload['action'];
      	$leopard    = $payload;
      	$store_id   = $leopard['datapacket']['orderinfo']['store_id'];
      	$store_spec = json_decode(Helper::getStoreSpec($store_id), true);
      	$url        = Config::get('urls.courier_urls.leopard');
      	$order_id   = $leopard['datapacket']['orderinfo']['order_id'];  
      	$address    = $leopard['datapacket']['shipping_address']['address'].', '.$leopard['datapacket']['shipping_address']['address_2'];
      	$order_total_price = Helper::getOrderTotalAmount($leopard['datapacket']['total']);      
      	$city       = $leopard['datapacket']['shipping_address']['city'];                
      	$productDetails = $leopard['datapacket']['cart'];                 
      	$products   = Helper::productDetails($productDetails);
     	  $weight     = (($leopard['datapacket']['weight'] && $leopard['datapacket']['weight'] >= 0.5) ? $leopard['datapacket']['weight'] : '0.5') * 1000;
      	$origin_city = $store_spec['shippingdetails']['variables']['origincity'];
      	$origin_city = $this->getLeopardCityID($origin_city);
      	$destination_city = $this->getLeopardCityID($city);
        
      	$curl_params = array(
          	'api_key'                      => $leopard['credentials']['api_key'],
          	'api_password'                 => $leopard['credentials']['api_password'],
          	'booked_packet_weight'         => $weight,  // weight in grams
          	'booked_packet_vol_weight_w'   => '',       
          	'booked_packet_vol_weight_h'   => '',       // OPTIONAL
          	'booked_packet_vol_weight_l'   => '',
          	'booked_packet_no_piece'       => $products['qty'],
          	'booked_packet_collect_amount' => $order_total_price,
          	'booked_packet_order_id'       => $leopard['datapacket']['orderinfo']['order_id'], // optinal
          	'origin_city'                  => $origin_city,    // $json['origincity']
          	'destination_city'             => $destination_city,     
          	'shipment_name_eng'            => $leopard['datapacket']['orderinfo']['store_name'],
          	'shipment_email'               => $store_spec['store_info']['email'],
          	'shipment_phone'               => $store_spec['store_info']['phone'],
          	'shipment_address'             => $store_spec['store_info']['address'],
          	'consignment_name_eng'         => $leopard['datapacket']['customer']['firstname'].' '.$leopard['datapacket']['customer']['lastname'],
          	'consignment_email'            => $leopard['datapacket']['customer']['email'],
          	'consignment_phone'            => $leopard['datapacket']['customer']['telephone'],
          	'consignment_phone_two'        => '',      //OPTIONAL
          	'consignment_phone_three'      => '',
      		  'consignment_address'          => $address,
          	'special_instructions'         => 'Instructions'
      	);          
        dd(json_encode($curl_params));
      	$res    = Helper::curlRequest($url, $curl_params);
      	$buffer = json_decode($res, true);
        
      	if($buffer['status'] == 1){
          	$traking_no = $buffer['track_number'];
          	$order_id   = $leopard['datapacket']['orderinfo']['order_id'];          
          	$store      = Helper::GetStoreNameByIDOrEmail($leopard['datapacket']['orderinfo']['store_id']);  
          	$store_url  = $leopard['datapacket']['orderinfo']['store_url'];                     	         	                             
            $data = ['successMessage' => 'order Shipped to Leopard', 'order_id' => $order_id, 'store_name' => $store, 'store_url' => $store_url, 'tracking_id' => $traking_no, 'courier_company' => 'leopard'];
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => [$data]]);          	                    
      	} else {
            $msg = $buffer['error'];
            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $buffer['error']]]); 
      	}     

      	return $response; 
    }

    // get city id for leopard 
    public function getLeopardCityList($payload) {    

        $action = $payload['action'];
        $leopard_city_list = json_decode(file_get_contents('cities/leopard_city_list.json'), true);      

        $response = array_merge(Helper::constantResponse($action, 200, ''), ['datapacket' => [$leopard_city_list]]);

        return $response;

        // array to add comma
        /*$leopard_city = [];
        foreach ($leopard_city_list['city_list'] as $key => $city) {
            array_push($leopard_city, strtoupper($city['name']));
        }
        dd(str_replace(',', '", "', implode (",", $leopard_city)));*/
    }

    // get city id for leopard 
    public function getLeopardCityCode($payload) {   

        $city_code = 'self';
        $action = $payload['action'];
        $city   = $payload['dataPacket']['city'];
        $leopard_city_list = json_decode(file_get_contents('cities/leopard_city_list.json'), true);

        foreach ($leopard_city_list['city_list'] as $key => $value) {              
            if($value['name'] == ucfirst($city)) {
                $city_code = $value['id'];
                break;
            }             
        }

        $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['cityCode' => $city_code]]);

        return $response;
    }

    // get city id for leopard 
    public function getLeopardCityID($city) {   

        $city_id = 'self';
        $leopard_city_list = json_decode(file_get_contents('cities/leopard_city_list.json'), true);

        foreach ($leopard_city_list['city_list'] as $key => $value) {              
            if($value['name'] == ucfirst($city)) {
                $city_id = $value['id'];
                break;
            }             
        }        

        return $city_id;
    }

    // track leopard parsal
    public function trackLeopardParsal($payload)
    {
        $action      = $payload['action'];
        $url         = Config::get('urls.courier_urls.leopard_track_parcel');
        $curl_params = [
            'api_key'       => $payload['credentials']['api_key'],
            'api_password'  => $payload['credentials']['api_password'],
            'track_numbers' => $payload['dataPacket']['tracking_no']
        ];
        
        $curl_response = json_decode(Helper::curlRequest($url, $curl_params), true);                
         dd($curl_response);
        if( $curl_response['status'] == 1 ){
            $order_status = $curl_response['packet_list'][0]['Tracking Detail'];
            unset($curl_response['packet_list'][0]['Tracking Detail']);
            $parcel = $curl_response['packet_list'];

            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['trackLeopardParsal' => $parcel, 'orderLeopardStatus' => $order_status]]);
        } else {
            $msg = 'Invalid Tracking No';
            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
        }

        return response()->json($response);
    } 
}
