<?php

namespace App\Http\Controllers\Connector;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

// MODELS
use App\Models\Shipping;
use App\Models\VendorOrderStatus;

class MagentorestController extends Controller
{
    protected $license_key, $order_obj;

    public function index($license_key, $payload)
    {	
    	@file_put_contents('magentorest.json', json_encode($payload));
    	$this->license_key = $license_key;

    	$store = Helper::getStoreInfo($this->license_key);
    	$store_info = $store['storeInfo'];
		$store_id = $store_info['technify_store_id'];
		$order_id = $payload['dataPacket']['entity']['id']; 		
		$endpoint = $store_info['endpoint'];

    	if($store_info->auth !== ''){
			$auth = json_decode($store_info->auth, true);			
		    $auth_endpoint = $endpoint.'/V1/integration/admin/token';
			$basic_auth = $auth['basic_auth'];
            
            $token = json_decode($this->curl($auth_endpoint, 'POST', $basic_auth), true);
            $order_endpoint = $endpoint.'/V1/technify/order/details';   
            $header = ['authorization' => 'Bearer '.$token, 'Content-type' => 'application/json']; 
            $order_obj = json_decode(Helper::curl($order_endpoint, 'POST', compact('order_id'), $header), true);  
            
            if( gettype($token) == 'string' && !isset($order_obj['message']) ) {

                $this->order_obj = $order_obj;                     
                $this->order_obj = $this->transformOrderObject();  
                $filter = compact('store_id', 'order_id') ;

                @file_put_contents('magentorest.json', json_encode($this->order_obj));
                // check order exist    
                $check_order_exist = Helper::checkOrderExist($filter);      

                if( $check_order_exist['response'] ){
                    $order_fileds = Helper::splitOrderObjectToFields($this->order_obj);  
                    $check_order_exist['orderDetails']->update($order_fileds);                                      
                } else {            
                    $order_fileds = array_merge($filter, Helper::splitOrderObjectToFields($this->order_obj));            
                    $order_fileds = array_merge($order_fileds, ['status' => 0]);
                    Shipping::create($order_fileds);
                }  
                
                $order_status_id = $this->order_obj['dataPacket']['order_status']['order_status_id'];
                $action = 'shipOrder';
                $datapacket = compact('order_id', 'order_status_id');
                $payload = Helper::createCurlPayload($action, $datapacket);
                $payload['license_key'] = $license_key; 
                
            } else {
            	$payload = ['response' => false, 'errorMessage' => 'something wrong with auth'];
            }	    	
		} else {
			$payload = ['response' => false, 'errorMessage' => 'please provide authuntication'];
		}
	    
        return $payload;
    }

	// tranform order object woo-commerce to technify
    public function transformOrderObject()
    {	    	
    	// transform nifi 
		$nifi_endpoint = Config::get('urls.app_urls.nifi_endpoint');   
		$header = ['storeid-entity' => 'magentorest_technify-order-spec'];
		$datapacket = json_decode(Helper::curl($nifi_endpoint, 'POST', $this->order_obj, $header), true);
		$payload['action'] = 'pushOrderObject';			 						
		$payload['license_key'] = $this->license_key;			 						
		$payload['dataPacket'] = $datapacket;

		return $payload;
    }

    // pull Magento rest order status
    public function pullOrderStatus($store_info)
    {   
        $store_id = $store_info->technify_store_id;
        $auth = $store_info->auth;
        $endpoint = $store_info->endpoint;        
        
        if( $auth && $endpoint ) {

            /*$basic_auth = json_decode($store_info->auth, true)['basic_auth'];      
            $auth_endpoint = $endpoint.'integration/admin/token';                  
            $header = ['Content-Type' => 'application/json'];
            $token = json_decode(Helper::curl($auth_endpoint, 'POST', $basic_auth, $header), true);*/
            
            $order_status_endpoint = $endpoint.'/default/V1/technify/services/order/statuses';                       
            $header = [/*'authorization' => 'Bearer '.$token, */'Content-type' => 'application/json']; 
            $order_status = json_decode(Helper::curl($order_status_endpoint, 'POST', [], $header), true);
            
            if( is_array($order_status) ) {   
                foreach ( $order_status as $key => $value ) {    

                    $status = substr($value, strpos($value, '::') + 2);
                    $data = ['store_id' => $store_id, 'order_status_id' => $status, 'order_status' => $status, 'status' => 0, 'title' => ''];
                    $filter = ['store_id' => $store_id, 'order_status_id' => $status];
                    $get_order_status = VendorOrderStatus::where($filter)->first();                     
                    if( count($get_order_status) > 0 ) {
                        $get_order_status->update(['order_status' => $status]);
                    } else {
                        VendorOrderStatus::create($data);
                    }
                }
                $response = ['response' => true, 'orderStatus' => $order_status];
            } else {
                $response = ['response' => false, 'errorMessage' => 'something wrong with order status'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'please provide auth/endpoint'];
        }

        return $response; 
    }

    // magento soap order statuses fix
    public function orderStatus()
    {
    	return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'payment_approved' => 'Payment Approved',
    		'confirmed' => 'Confirmed',
    		'canceled' => 'Canceled',
    		'hold' => 'Hold',
    		'unhold' => 'Un hold',
            'edit' => 'Edit',
            'creditmemo' => 'Credit memo',
            'invoice' => 'Invoice',
            'reorder' => 'Reorder',
            'ship' => 'Ship',
            'comment' => 'Comment',
            'product_permission_denied' => 'product permission denied',
    	];
	}

    // update order status in woo commerce 
    public function updateOrderStatus($endpoint, $auth, $packet)
    {	        
        $order_id = $packet['order_id'];  
        $store_id = $packet['store_id'];  
        $track_number = $packet['tracking_id'];  
        $title = $packet['courier_company'];  
        $extension_attributes = [];
    	$comment = $packet['successMessage'];  
        $order_status = $packet['order_status'];
        $update_status = Helper::getShippingSettingsOrderStatus($store_id, 'after_shipped')['orderStatusID'];        
        $auth = json_decode($auth, true)['basic_auth'];
        
        $auth_endpoint = $endpoint.'/V1/integration/admin/token'; 
        $token = json_decode($this->curl($auth_endpoint, 'POST', $auth), true);    
        $order_update_endpoint = $endpoint.'/default/V1/technify/services/order/status/update';
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token];       
        $payload = $this->createPayload($order_id, $update_status, $comment, $extension_attributes, $track_number, $title, $title);
        $curl_resp = json_decode(Helper::curl($order_update_endpoint, 'POST', $payload, $headers), true);
        
        if( isset($curl_resp['message']) ) {
            $response = ['response' => false, 'errorMessage' => $curl_resp['message']];
        } else {
            $response = ['response' => true];
        }

        return $response;
    }

    public function createPayload($order_id, $status, $comment, $extension_attributes, $track_number, $title, $carrier_code)
    {
        return ['order_id' => $order_id, 'order_status' => ['status' => $status, 'state' => "", 'comment' => $comment, 'notify' => false, 'tracks' => [compact('order_id', 'extension_attributes', 'track_number', 'title', 'carrier_code')]]];
    }

    // curl
    public function curl($url, $request, $payload = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => $request,
            CURLOPT_POSTFIELDS      => $payload
            )); 
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }  
    }

    public function abc()
    {
        $endpoint = 'https://rangrasiya.com.pk/index.php/rest';
        $auth = '{"basic_auth":{"username":"technify","password":"t3chnify.pk"}}';
        $packet = ['successMessage' => 'ORDER SUCCESSFULLY SHIPPED TO callcourier CHECK EMAIL OR VISIT YOUR TECHNIFY DASHBOARD', 'courier_company' => 'callcourier', 'order_id' => '000004755', 'tracking_id' => '1234567', 'order_status' => 'payment_approved', 'store_url' => 'asd', 'store_id' => 200012];
        $this->updateOrderStatus($endpoint, $auth, $packet);
    }
}
