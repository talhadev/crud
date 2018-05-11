<?php

namespace App\Http\Controllers\Connector;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

// MODELS
use App\Models\Shipping;
use App\Models\VendorOrderStatus;

class NewopencartController extends Controller
{
    protected $license_key, $order_obj;
    public function index($license_key, $payload)
    {
    	$this->license_key = $license_key;
    	$store = Helper::getStoreInfo($this->license_key);
		$store_info = $store['storeInfo'];
		$store_id   = $store_info['technify_store_id'];
		$endpoint = $store_info['endpoint'];
		$this->order_obj = $payload['dataPacket'];
		$order_id = $this->order_obj['order_id'];
        $order_status_id = $this->order_obj['order_status']['order_status_id']; 		
		$filter = compact('store_id', 'order_id');

		$transform_order_object = $this->transformOrderObject(); 
		// check order exist
		$check_order_exist = Helper::checkOrderExist($filter);					
		if($check_order_exist['response']) {
			$order_fileds = Helper::splitOrderObjectToFields($transform_order_object, $store_id);		
            $check_order_exist['orderDetails']->update($order_fileds);						
		} else {
			$order_fileds = array_merge($filter, Helper::splitOrderObjectToFields($transform_order_object, $store_id));			
            $order_fileds = array_merge($order_fileds, ['status' => 0]);
			Shipping::create($order_fileds);
		}
		$action = 'shipOrder';
        $order_status = $transform_order_object['dataPacket']['order_status']['status']; 
		$datapacket = compact('order_id', 'order_status', 'order_status_id');
		$payload = Helper::createCurlPayload($action, $datapacket);
		$payload['license_key'] = $license_key;
        
    	return $payload;
    }

    // transform order object
    public function transformOrderObject()
    {
    	// transform nifi 
		$nifi_endpoint = Config::get('urls.app_urls.nifi_endpoint');   
		$header = ['storeid-entity' => 'opencart_technify-order-spec'];
		$datapacket = json_decode(Helper::curl($nifi_endpoint, 'POST', $this->order_obj, $header), true);
		$payload['action'] = 'pushOrderObject';			 						
		$payload['license_key'] = $this->license_key;			 						
		$payload['dataPacket'] = $datapacket;

		return $payload;
    }
    // pull order statuses
    public function pullOrderStatus($store_info)
    {	
    	$action = 'pullOrderStatus';
    	$endpoint = $store_info->endpoint;
        $params = Helper::createCurlPayload($action, []);

        $params['license_key'] = $store_info->uuid;
        $curl_response = json_decode(Helper::curl($endpoint, 'POST', $params, []), true);          
        
        if( $curl_response['statusCode'] == 200 ) {
            $order_status = $curl_response['dataPacket']['orderStatus'];        
            foreach ( $order_status as $key => $value ) {               
                $data = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $value['order_status_id'], 'order_status' => $value['name'], 'status' => 0, 'title' => ''];                
                $filter = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $value['order_status_id']];
                $get_order_status = VendorOrderStatus::where($filter)->first(); 
                if( count($get_order_status) > 0 ) {
                    $get_order_status->update(['orderStatus' => $value['name']]);
                } else {
                    VendorOrderStatus::create($data);
                }
            } 
            $response = ['response' => true];
        } else {
            $response = ['response' => false, 'errorMessage' => $curl_response['dataPacket']['errorMessage']];
        }

    	return $response;
    }

    // update order status in big commerce vendor 
    public function updateOrderStatus($endpoint, $auth, $packet)
    {                   
        $store_id = $packet['store_id'];        
        $order_id = $packet['order_id'];  
        $status = $packet['order_status'];          
        $order_status = Helper::getShippingSettingsOrderStatus($store_id, 'after_shipped')['orderStatusID'];        
        $auth = json_decode($auth, true);
        $license_key = $auth['basic_auth']['license_key'];        
        $order_history_comment = $packet['successMessage'];

        $datapacket  = compact('order_id', 'order_status', 'order_history_comment');            
        $payload = Helper::createCurlPayload('updateOrderStatus', $datapacket); 
        $payload['license_key'] = $license_key;          
        $curl_resp = json_decode(Helper::curl($endpoint, 'POST', $payload, []), true);
        
        if( $curl_resp['dataPacket']['response'] ) {
            $response = ['response' => true];
        } else {
            $response = ['response' => false, 'errorMessage' => $curl_resp['errorMessage']];
        }

        return $response;
    }
}
