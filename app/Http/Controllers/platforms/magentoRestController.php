<?php

namespace App\Http\Controllers\platforms;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Helper;
use Config;

// MODELS
use App\Models\Stores;

class magentoRestController extends Controller
{
    protected $username = 'services@technify.pk', $password = 'user@technify';

    public function index($payload, $license_key, $store_url) {
        
        ini_set('max_execution_time', 300); 
        $this->license_key = $license_key;              

        $shipped_url = Config::get('urls.curl_urls.shipping_endpoint');        
        $store_info = Helper::getStoreInfo($this->license_key)['storeInfo'];

        $auth_token_url = $store_url.'/index.php/rest/default/V1/integration/admin/token';            
        $get_order_object_url = $store_url.'/index.php/rest/default/V1/technify/order/details';
        $order_update_endpoint = $store_url.'/index.php/rest/default/V1/technify/services/order/status/update';

        if ($payload && $license_key && $store_url) {

            $store_info = Helper::getStoreInfo($this->license_key);
            if( $store_info['response'] ) {
                $store_info   = $store_info['storeInfo'];
                $store_id     = $store_info->technify_store_id;
                $pyld_decode  = json_decode($payload, true);
                $order_id     = $pyld_decode['dataPacket']['entity']['id'];                
                $auth         = json_decode($store_info->auth, true)['basic_auth'];                
                
                $auth_token    = json_decode(Helper::curlRequest($auth_token_url, $auth));
                
                $order_object_payload  = json_encode(compact('order_id'));
                $order_object_auth  = "authorization: Bearer ".$auth_token;
                $order_object_content_type = "content-type: application/json";

                $this->order_obj  = json_decode(Helper::curlRequestWithHeaders2($get_order_object_url, $order_object_payload, $order_object_auth, $order_object_content_type), true);                   
                $this->order_obj = $this->transformOrderObject();
                
                $order_status =  $this->order_obj['dataPacket']['order_status']['status'];

                if( strtolower($order_status) == 'processing' ) {
                    
                    $response = json_decode(Helper::curlRequestWithBasicAuth($shipped_url, $this->order_obj, $this->username, $this->password), true);                                        
                    $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$auth_token];
                    
                    if( $response['statusCode'] == 200 ) {                                                                                    
                        $comment = $response['dataPacket']['successMessage'];                        
                        $payload = $this->createPayload($order_id, $order_status, $comment);           
                        $post_comment = json_decode(Helper::curl($order_update_endpoint, 'POST', $payload, $headers), true);         
                        dd($post_comment);

                    } elseif( $response['statusCode'] == 500 ){

                        $comment = $response['dataPacket']['errorMessage'];
                        $payload = $this->createPayload($order_id, $order_status, $comment);           
                        $post_comment = json_decode(Helper::curl($order_update_endpoint, 'POST', $payload, $headers), true); 
                        dd($post_comment);

                    }
                } elseif(isset($order_status) && strtolower($order_status) == 'canceled') {

                    $this->order_obj['action'] = 'cancelShipment';
                    $response = json_decode(Helper::curlRequestWithBasicAuth($shipped_url, $this->order_obj, $this->username, $this->password), true);  

                    if( $response['statusCode'] == 200 ) {

                        $comment = $response['dataPacket']['successMessage'];
                        $payload = $this->createPayload($order_id, $order_status, $comment); 
                        $post_comment = json_decode(Helper::curl($order_update_endpoint, 'POST', $payload, $headers), true); 
                        dd($post_comment);

                    } elseif( $response['statusCode'] == 500 ){

                        $comment = $response['dataPacket']['errorMessage'];
                        $payload = $this->createPayload($order_id, $order_status, $comment); 
                        $post_comment = json_decode(Helper::curl($order_update_endpoint, 'POST', $payload, $headers), true); 
                        dd($post_comment);
                    }
                }             
            }

            return $response;
        }
    }

    // transform order object from infi
    public function transformOrderObject()
    {
        // transform nifi 
        $nifi_endpoint = Config::get('urls.app_urls.nifi_endpoint');   
        $header = ['storeid-entity' => 'magentorest_technify-order-spec'];        
        $datapacket = json_decode(Helper::curl($nifi_endpoint, 'POST', $this->order_obj, $header), true);        
        $payload['action'] = 'pushOrderObject';                                 
        $payload['timestamp'] = date('y-m-d h:i:s');                                 
        $payload['license_key'] = $this->license_key;                                   
        $payload['dataPacket'] = $datapacket;

        return $payload;
    }

    public function createPayload($order_id, $status, $comment)
    {
        return ['order_id' => $order_id, 'order_status' => ['status' => true, 'state' => $status, 'comment' => $comment, 'notify' => false, 'tracks' => ['order_id' => $order_id]]];
    }
}
