<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

class ApiKangarooController extends Controller
{
    // Parsal shipped to kangaroo 
    public function kangaroo($payload) {      

		    file_put_contents('kangaroo.json', json_encode($payload, true));   

	  	  $kangaroo = $payload;
	  
	  	  $order_total_price = Helper::getOrderTotalAmount($kangaroo['datapacket']['total']);

        $action = $payload['action'];
      	$address = $kangaroo['datapacket']['shipping_address']['address'].', '.$kangaroo['datapacket']['shipping_address']['address_2'];
      	$order_id = $kangaroo['datapacket']['orderinfo']['order_id'];      
      	$productDetails = $kangaroo['datapacket']['cart'];      
      	$products = Helper::productDetails($productDetails);      
      
    		$url         = Config::get('urls.courier_urls.kangaroo');
    	  //  $url=   'http://stagging.kangaroo.pk/orderapi.php';
        $clientid    = $kangaroo['credentials']['clientid'];
    		$pass        = $kangaroo['credentials']['password'];
    		$cname       = $kangaroo['datapacket']['customer']['firstname'].' '.$kangaroo['datapacket']['customer']['lastname'];
      	$caddress    = $address;
      	$cnumber     = $kangaroo['datapacket']['customer']['telephone'];
      	$amount      = $order_total_price;
      	$invoice     = 'invoive-'.rand(10000, 99999);        
      	$pname       = 'Products: '.$products['name'];      // optional
      	$pcode       = 'QTY: '.$products['qty'];      // optional
      	$city        = $kangaroo['origincity'];
      	$orderType   = $kangaroo['datapacket']['payment_method'][0]["code"];      

      	$curl_params = array(
          	"clientid" => $clientid,
          	"pass" => $pass,
          	"cname" => $cname,
          	"caddress" => $caddress,
          	"cnumber" => $cnumber,
          	"amount" => $amount,
          	"invoice" => $invoice,
          	"pname" => $pname,
          	"pcode" => $pcode,
          	"city" => $city,
          	"orderType" => $orderType,
      	);

        $curl_response = Helper::curlRequest($url, $curl_params);      	

      	$response = json_decode($curl_response, true);  
        
      	if(isset($response['order id'])){
          	$order_tracking_id = $response['order id'];
          	$order_id  = $kangaroo['datapacket']['orderinfo']['order_id'];          
          	$store     = Helper::GetStoreNameByIDOrEmail($kangaroo['datapacket']['orderinfo']['store_id']);  
          	$store_url = $kangaroo['datapacket']['orderinfo']['store_url'];                         	

            $data = ['successMessage' => 'order Shipped to kangaroo', 'order_id' => $order_id, 'store_name' => $store, 'store_url' => $store_url, 'tracking_id' => $order_tracking_id, 'courier_company' => 'kangaroo'];
          	$response = array_merge(Helper::constantResponse($action, 200, ''), ['datapacket' => $data]);
                            	
      	} else {
            $msg = 'something wrong';
          	$response = array_merge(Helper::constantResponse($action, 500, $msg), ['datapacket' => ['errorMessage' => $msg]]);
      	} 

      	return $response; 
    }

    // parsal cancel kangaroo
    public function cancelShipmentKangaroo($payload)
    {

        $tracking_no = $payload['dataPacket']['tracking_no']; 
        $action = $payload['action'];
        $url = 'http://kangaroo.pk/cancelapi.php';

        $curl_params = array(
            'clientid' => $payload['dataPacket']['credentials']['clientid'],                              
            'pass'     => $payload['dataPacket']['credentials']['password'],                         
            'orderid'  => $tracking_no
        );
            
        $curl_response = json_decode(Helper::curlRequest($url, $curl_params), true);
        
        if( $curl_response['orderresponse'] == "true" ) {
            $data     = ['successMessage' => $tracking_no.' has been cancelled'];
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $data]);  
        } else {
            $msg = 'Enter valid tracking no';
            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);  
        }   

        return $response;     
    }
}
