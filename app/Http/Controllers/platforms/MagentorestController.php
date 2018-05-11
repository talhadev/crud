<?php

namespace App\Http\Controllers\platforms;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

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

        if($store_info->auth !== '') {
            $auth = json_decode($store_info->auth, true);           
            $auth_endpoint = $endpoint.'integration/admin/token';
            $basic_auth = $auth['basic_auth'];
            
            // $token = json_decode($this->curl($auth_endpoint, 'POST', $basic_auth), true);  
            $token = '123456789';  
            
            if(gettype($token) == 'string') {

                $order_endpoint = $endpoint.'technify/order/details';     
                $header = ['authorization' => 'Bearer '.$token, 'Content-type' => 'application/json']; 
                // $order_obj = json_decode(Helper::curl($order_endpoint, 'POST', compact('order_id'), $header), true);
                $order_obj = json_decode($this->orderObject(), true);
                
                if(!isset($order_obj['message'])) {
                    $this->order_obj = $order_obj;                     
                    $transform_order_object = $this->transformOrderObject();  
                    
                    @file_put_contents('magentorest.json', json_encode($transform_order_object));
                    $check_order_exist = Helper::checkOrderExist(compact('store_id', 'order_id'));
                    dd($check_order_exist);
                    if($check_order_exist['response']) {
						$order_fileds = Helper::splitOrderObjectToFields($transform_order_object, $store_id);		
			            $check_order_exist['orderDetails']->update($order_fileds);						
					} else {
						$order_fileds = array_merge($filter, Helper::splitOrderObjectToFields($transform_order_object, $store_id));	
			            $order_fileds = array_merge($order_fileds, ['status' => 0]);
						Order::create($order_fileds);
					}  

					$action = 'shipOrder';
					$datapacket = compact('order_id', 'order_status_id');
					$payload = Helper::createCurlPayload($action, $datapacket);
					$payload['license_key'] = $license_key;
                }
            } else {
            	$payload = ['response' => false, 'errorMessage' => 'something wrong with auth'];
            }	  
        } else {
        	$payload = ['response' => false, 'errorMessage' => 'please provide authuntication'];
        }

        dd($payload);
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


}
