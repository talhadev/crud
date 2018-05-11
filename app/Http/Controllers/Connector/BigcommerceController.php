<?php

namespace App\Http\Controllers\Connector;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

//MODELS
use App\Models\VendorOrderStatus;
use App\Models\Shipping;

class BigcommerceController extends Controller 
{
    protected $license_key, $order_obj;
    public function index($license_key, $payload)
    { 
        $this->license_key = $license_key;   

    	$store = Helper::getStoreInfo($this->license_key);
		$store_info = $store['storeInfo']; 
		$store_id = $store_info['technify_store_id']; 
		$order_id = $payload['data']['id']; 		
		$endpoint = $store_info['endpoint'].'orders/'.$order_id; 	
        $filter = compact('store_id', 'order_id');

        if( $store_info->auth !== '' ){
            $auth = json_decode($store_info->auth, true); 

            if( isset($auth['oauth']) ) {                                       
                
                $oauth = $this->OAuthEndpoint($auth);
                $header = $oauth['header'];
                $endpoint = $oauth['endpoint'].'orders/'.$order_id;

                $o_endpoint = $endpoint.'.json';
                $p_endpoint = $endpoint.'/products.json';
                $s_endpoint = $endpoint.'/shippingaddresses.json';
                
                $o_curl_resp = json_decode(Helper::curl($o_endpoint, 'GET', [], $header), true);
                $p_curl_resp = json_decode(Helper::curl($p_endpoint, 'GET', [], $header), true);
                $s_curl_resp = json_decode(Helper::curl($s_endpoint, 'GET', [], $header), true);

                $this->order_obj = $o_curl_resp;
                $this->order_obj['products'] = $p_curl_resp;
                $this->order_obj['shipping_addresses'] = $s_curl_resp;
                
            } else {    
                // basic auth
                $endpoint = $this->basicAuthEndpoint($endpoint, json_encode($auth));  
                // endpoints
                $o_endpoint = $endpoint.'.json'; 
                $p_endpoint = $endpoint.'/products.json';
                $s_endpoint = $endpoint.'/shippingaddresses.json';
                // curl request bigcommerce
                $o_curl_resp = json_decode(file_get_contents($o_endpoint), true);
                $p_curl_resp = json_decode(file_get_contents($p_endpoint), true);
                $s_curl_resp = json_decode(file_get_contents($s_endpoint), true);

                $this->order_obj = $o_curl_resp;
                $this->order_obj['products'] = $p_curl_resp;
                $this->order_obj['shipping_addresses'] = $s_curl_resp;                
            }
            
            $transform_order_object = $this->transformOrderObject();              
            $check_order_exist = Helper::checkOrderExist($filter);
            if( $check_order_exist['response'] ){
                $order_fields = Helper::splitOrderObjectToFields($transform_order_object, $store_id);        
                $check_order_exist['orderDetails']->update($order_fields);                                      
            } else {            
                $order_fields = array_merge($filter, Helper::splitOrderObjectToFields($transform_order_object, $store_id));            
                $order_fields = array_merge($order_fields, ['status' => 0]);
                Shipping::create($order_fields);
            }
            
            $order_status_id = $transform_order_object['dataPacket']['order_status']['order_status_id'];
            $action = 'shipOrder';
            $datapacket = compact('order_id', 'order_status_id');
            $payload = Helper::createCurlPayload($action, $datapacket);
            $payload['license_key'] = $this->license_key;

        } else {
            $payload = ['response' => false, 'errorMessage' => 'please provide authuntication'];
        }
        
    	return $payload;
    }

    // update order status in big commerce vendor 
    public function updateOrderStatus($endpoint, $auth, $packet)
    {	    	
        $order_id = $packet['order_id'];
    	$store_id = $packet['store_id'];  
        // $status_id = $packet['order_status'];  
    	$status_id = Helper::getShippingSettingsOrderStatus($store_id, 'after_shipped')['orderStatusID'];
    	$auth = json_decode($auth, true);

    	if(isset($auth['oauth'])){
    		$oauth = $this->OAuthEndpoint($auth);    		   		
    		$endpoint = $oauth['endpoint'].'orders/'.$order_id.'.json';
    		$headers   = $oauth['header'];
    	} else {
    		$endpoint = $this->basicAuthEndpoint($endpoint, json_encode($auth)).'orders/'.$order_id.'.json'; 
    		$headers = ['Content-Type' => 'application/json'];
    	}
    	
    	$curl_resp = json_decode(Helper::curl($endpoint, 'PUT', compact('status_id'), $headers), true);       	
    	if( isset(end($curl_resp)['message']) ) {
    		$response = ['response' => false, 'errorMessage' => end($curl_resp)['message']];
    	} else {
    		$response = ['response' => true];
    	}
    	return $response;
    }

    // pull order statuses
    public function pullOrderStatus($store_info)
    {       	
    	$endpoint = $store_info->endpoint;
    	$auth = json_decode($store_info->auth, true);

        if($endpoint && $auth) {
            if(isset($auth['oauth'])) {
                $oauth = $this->OAuthEndpoint($auth);
                $endpoint = $oauth['endpoint'].'order_statuses.json';
                $headers  = $oauth['header'];
                $order_status = json_decode(Helper::curl($endpoint, 'GET',[], $headers), true);             
            } else {
                $endpoint = $this->basicAuthEndpoint($endpoint, json_encode($auth));        
                $order_status = json_decode(@file_get_contents($endpoint.'order_statuses.json'), true);
            }
            
            if($order_status !== null) {
                
                foreach ( $order_status as $key => $value ) {       
                    $data = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $value['id'], 'order_status' => $value['name'], 'status' => 0, 'title' => ''];                
                    $filter = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $value['id']];
                    $get_order_status = VendorOrderStatus::where($filter)->first(); 
                    if( count($get_order_status) > 0 ) {
                        $get_order_status->update(['orderStatus' => $value['name']]);
                    } else {
                        VendorOrderStatus::create($data);
                    } 
                } 
                $response = ['response' => true];
            } else {
                $response = ['response' => false, 'errorMessage' => 'something wrong with order status'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'please provide auth/endpoint'];
        }
    	
    	return $response;
    }

    // create endpoint auth wise
    public function OAuthEndpoint($auth)
    {    	
    	// headers and endpoint
		$content_type = 'application/json'; 
		$client = $auth['oauth']['client'];
		$token  = $auth['oauth']['token'];
		$hash   = $auth['oauth']['hash'];
		$header = ['Content-Type' => $content_type, 'X-Auth-Client' => $client, 'X-Auth-Token' => $token];
		$endpoint = 'https://api.bigcommerce.com/stores/'.$hash.'/v2/';

		return ['endpoint' => $endpoint, 'header' => $header];			
    }

    // create big commerce url using auth
    public static function basicAuthEndpoint($endpoint, $auth)
    {   
        $auth = json_decode($auth, true)['basic_auth'];                 
        $username = $auth['username']; $pass = $auth['password']; 
        $auth = $username.':'.$pass.'@';
        $endpoint = substr_replace($endpoint, $auth, (stripos($endpoint, ':') + 3), 0);
        return $endpoint;
    }

    public function transformOrderObject()
    {
        // transform nifi 
        $nifi_endpoint = Config::get('urls.app_urls.nifi_endpoint');  
        $header = ['storeid-entity' => 'bigcommerce_technify-order-spec'];
        $datapacket = json_decode(Helper::curl($nifi_endpoint, 'POST', $this->order_obj, $header), true);        
        $payload['action'] = 'pushOrderObject';
        $payload['license_key'] = $this->license_key;         
        $payload['dataPacket'] = $datapacket;

        return $payload;
    }
}
