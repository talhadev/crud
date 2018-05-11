<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use nusoap_client;
use Config;

class ApiTcsController extends Controller
{
    // packet shipped to TCS
    public function tcs($payload) {         
        
     file_put_contents('tcs.json', json_encode($payload ,true), true);   
     
     $tcs = $payload;
     $action = $payload['action'];
     $order_total_price = Helper::getOrderTotalAmount($tcs['datapacket']['total']);
     $address = $tcs['datapacket']['shipping_address']['address'].', '.$tcs['datapacket']['shipping_address']['address_2'];
     $city = $tcs['ifCityIsNull'];
     $order_id = $tcs['datapacket']['orderinfo']['order_id'];      
     $productDetails = $tcs['datapacket']['cart'];      
     
     $products = Helper::productDetails($productDetails);      

     $client = new nusoap_client(Config::get('urls.courier_urls.tcs'), true);


     $result = $client->call("InsertData", [
        'userName'             => $tcs['credentials']['username'], 
        'password'             => $tcs['credentials']['password'], 
        'costCenterCode'       => $tcs['credentials']['costCenterCode'], 
        'consigneeName'        => $tcs['datapacket']['customer']['firstname'].' '.$tcs['datapacket']['customer']['lastname'],
        'consigneeAddress'     => $address,
        'consigneeMobNo'       => $tcs['datapacket']['customer']['telephone'],
        'consigneeEmail'       => $tcs['datapacket']['customer']['email'],
        'originCityName'       => $tcs['origincity'],        
        'destinationCityName'  => $city,
        'pieces'               => 1,   // EGO says this is one
        'weight'               => ($tcs['datapacket']['weight'] && $tcs['datapacket']['weight'] >= 0.5) ? $tcs['datapacket']['weight'] : '0.5',
        'codAmount'            => $order_total_price,
        'custRefNo'            => $order_id,
        'productDetails'       => 'Products: '.$products['name'].'-----QTY: '.$products['qty'],
        'fragile'              => 'No',
        'services'             => 'O',
        'remarks'              => 'QTY: '.$products['qty'],
        'insuranceValue'       => '0',
        ]);   
     
     if(isset($result['InsertDataResult']) && $result['InsertDataResult'] !== 'Invalid City.') {
       $order_tracking_id = $result['InsertDataResult'];
       $order_id  = $tcs['datapacket']['orderinfo']['order_id'];          
       $store     = Helper::GetStoreNameByIDOrEmail($tcs['datapacket']['orderinfo']['store_id']);
       $store_url = $tcs['datapacket']['orderinfo']['store_url'];          
       $orderinfo = $payload;
       
       $data = ['successMessage' => 'order Shipped to TCS', 'order_id' => $order_id, 'store_name' => $store, 'store_url' => $store_url, 'tracking_id' => $order_tracking_id, 'courier_company' => 'TCS'];
       $response = array_merge(Helper::constantResponse($action, 200, ''), ['datapacket' => $data]);

     } else {
       $msg = 'something wrong with tcs or invalid';
       $response = array_merge(Helper::constantResponse($action, 500, $msg), ['datapacket' => ['errorMessage' => $msg]]);
     } 

     return $response;
    }

    // parsal cancel TCS
    public function cancelShipmentTcs($payload) {        
        
        $tracking_no = $payload['dataPacket']['tracking_no']; 
        $action = $payload['action'];        

        if( $tracking_no ) {
            $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);
            $result = $client->call("CancelShipment", [
              'userName'             => $payload['credentials']['username'], 
              'password'             => $payload['credentials']['password'], 
              'consigneeNumber'      => $tracking_no, 
            ]);
        }        
        
        if( $result['CancelShipmentResult'] == true ) {
            $data     = ['successMessage' => $tracking_no.' has been cancelled'];
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $data]);  
        } else {
            $msg = 'Enter valid tracking no';
            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);  
        }      

        return $response;  
    }

    // tcs city list
    public function getTcsCityList($payload)
    {
    	$action = $payload['action'];
    	$client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);        
        $result = $client->call("GetAllCities", [
          	'userName' => $payload['credentials']['username'], 
          	'password' => $payload['credentials']['password']
        ]);
        $tcs_city_list = $result['GetAllCitiesResult']['diffgram']['NewDataSet']['Table'];
        $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['TcsCityList' => $tcs_city_list]]);

        return $response;
    }

    // tcs country list
    public function getTcsOriginCityList($payload)
    {
    	$action = $payload['action'];
    	$client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);        
        $result = $client->call("GetAllOriginCities", [
          	'userName' => $payload['credentials']['username'], 
          	'password' => $payload['credentials']['password']
        ]); 
        $tcs_origin_city_list = $result['GetAllOriginCitiesResult']['diffgram']['NewDataSet']['Table'];
        $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['TcsCityList' => $tcs_origin_city_list]]);

        return $response;
    }

    // tcs country list
    public function getTcsCountryList($payload)
    {
    	$action = $payload['action'];
    	$client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);        
        $result = $client->call("GetAllCountries", [
          	'userName' => $payload['credentials']['username'], 
          	'password' => $payload['credentials']['password']
        ]); 
        $tcs_country_list = $result['GetAllCountriesResult']['diffgram']['NewDataSet']['Table'];
        $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['TcsCityList' => $tcs_country_list]]);

        return $response;
    }

    // track TCS order by tracking no
    public function trackTcsParsal($payload)
    {   
        $action = $payload['action'];
        $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);        
        $result = $client->call("GetCNDetailsByReferenceNumber", [
          	'userName'            => $payload['credentials']['username'], 
          	'password'            => $payload['credentials']['password'], 
          	'customerReferenceNo' => $payload['dataPacket']['customerReferenceNo'] 
        ]);   

        $track_parsal = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table'];
        
        $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['TrackTcsParsal' => $track_parsal]]);

        return $response;
    }
}
