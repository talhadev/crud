<?php

namespace App\Http\Controllers\Connector;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

// MODELS
use App\Models\Shipping;
use App\Models\VendorOrderStatus;

class WoocommerceController extends Controller
{
	protected $license_key, $order_obj, $store_info;

    public function index($license_key, $order_obj)
    {
    	$this->license_key = $license_key;
    	if(isset($order_obj['order'])) {
            $this->order_obj = $order_obj['order'];
            $header = ['storeid-entity' => 'woocommerce_v1_technify-order-spec'];
        } else {
            $this->order_obj = $order_obj;
            $header = ['storeid-entity' => 'woocomerce_technify-order-spec'];
        }
        
    	$store = Helper::getStoreInfo($this->license_key);
    	$store_info = $store['storeInfo'];
        $store_id = $store_info['technify_store_id'];
        $order_id = $this->order_obj['id'];
		$order_status_id = $this->order_obj['status'];
        $filter = compact('store_id', 'order_id');
		
        // check order exist
        $transform_order_object = $this->transformOrderObject($header);    
        
		$check_order_exist = Helper::checkOrderExist($filter);		       		
    	if( $check_order_exist['response'] ){
            $order_fileds = Helper::splitOrderObjectToFields($transform_order_object);  
            $check_order_exist['orderDetails']->update($order_fileds);                                      
        } else {            
            $order_fileds = array_merge($filter, Helper::splitOrderObjectToFields($transform_order_object));            
            $order_fileds = array_merge($order_fileds, ['status' => 0]);
            Shipping::create($order_fileds);
        }
        
        $action = 'shipOrder';
        $datapacket = compact('order_id', 'order_status_id');
        $payload = Helper::createCurlPayload($action, $datapacket);
        $payload['license_key'] = $license_key;	
	    
    	return $payload;
    }

    // tranform order object woo-commerce to technify
    public function transformOrderObject($header)
    {
    	// transform nifi
		$nifi_endpoint = Config::get('urls.app_urls.nifi_endpoint');  
		$datapacket = json_decode(Helper::curl($nifi_endpoint, 'POST', $this->order_obj, $header), true);		
        $payload = Helper::createCurlPayload('pushOrderObject', $datapacket);
		$payload['license_key'] = $this->license_key;					

		return $payload;
    }

    // pull woo commerce order status
    public function pullOrderStatus($store_info)
    {
        $this->store_info = $store_info;
        $order_status = $this->orderStatus();        
        if($order_status !== null) {                          
            foreach ( $order_status as $key => $value ) {               
                $data = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $key, 'order_status' => $value, 'status' => 0, 'title' => ''];                
                $filter = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $key];
                $get_order_status = VendorOrderStatus::where($filter)->first(); 
                if( count($get_order_status) > 0 ) {
                    $get_order_status->update(['orderStatus' => $value]);
                } else {
                    VendorOrderStatus::create($data);
                } 
            } 
            $response = ['response' => true];
        } else {
            $response = ['response' => false, 'errorMessage' => 'something wrong with order status'];
        }  
        return $response; 
    }

    public function orderStatus()
    {        
        $order_status = json_decode(Helper::curlSentRequest($this->store_info->store_url.'wp-json/', 'GET'), true);
        $order_status = isset($order_status['routes']['/wc/v1/orders/(?P<id>[\d]+)']['endpoints'][1]['args']['status']['enum']) ? $order_status['routes']['/wc/v1/orders/(?P<id>[\d]+)']['endpoints'][1]['args']['status']['enum'] : null;
        if($order_status) {
            $order_status = array_combine($order_status, $order_status);
        }
        
        return $order_status;
        /*return [
            'pending' => 'Pending payment',
            'processing' => 'Processing',
            'on-hold' => 'On hold',
            'completed' => 'Completed',
            'cancelled' => 'Canceled',
            'refunded' => 'Refunded',
            'failed' => 'Failed'
        ];*/
    }

    public function updateOrderStatus($endpoint, $auth, $packet)
    {
        $store_id = $packet['store_id'];        
        $order_id = $packet['order_id']; 
        $status = Helper::getShippingSettingsOrderStatus($store_id, 'after_shipped')['orderStatusID'];        
        $auth = json_decode($auth, true);

        if( $store_id == 400007 ) {
            $action = 'woocommerceUpdateStatus';
            $param['endpoint'] = $endpoint;
            $param['auth'] = $auth;
            $param['order_id'] = $order_id;
            $param['order_status'] = $status;
            $payload = Helper::createCurlPayload($action, $param);
            $endpoint = 'https://api.technify.pk/smsmodule';
            return $curl_resp = json_decode(Helper::curl($endpoint, 'POST', $payload), true); 
        } else {
                                                        
            $headers = ['Content-Type' => 'application/json'];                    

            if( substr($endpoint, 0, 5) == 'https' ) {
                $endpoint = $this->basicAuthEndpoint($endpoint.'orders/'.$order_id, json_encode($auth)); 
                $param = compact('status');
            } else {            
                $store_url = $packet['store_url'];
                $key = $auth['basic_auth']['consumer_key'];
                $secret = $auth['basic_auth']['consumer_secret'];
                $version = 'v1';
                $endpoint = 'http://wcapi.technify.pk/api/order-update';
                $param = compact('key', 'secret', 'version', 'status', 'store_url', 'order_id');            
            } 

            $curl_resp = json_decode(Helper::curl($endpoint, 'POST', $param, $headers), true);     
            if( isset($curl_resp['data']['status']) ) {
                // -- add order comment/note
                $response = ['response' => false, 'errorMessage' => $curl_resp['message']];
            } else {
                $response = ['response' => true];
            }

            return $response;
        }                
    }

    // create basic endpoint 
    public function basicAuthEndpoint($endpoint, $auth)
    {
        $auth = json_decode($auth, true)['basic_auth'];                 
        $key = $auth['consumer_key']; $secret = $auth['consumer_secret'];        
        $endpoint = $endpoint.'?consumer_key='.$key.'&consumer_secret='.$secret;        
        return $endpoint;
    }  
}    