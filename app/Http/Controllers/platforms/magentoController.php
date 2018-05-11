<?php

namespace App\Http\Controllers\platforms;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Helper;
use App\Models\Stores;
use SoapClient;

class magentoController extends Controller
{
    /*public function index($payload,$license_key,$store_url)
    {        
        $username = "ahsans895@gmail.com";
        $password = "ahsan11";
        $nifi_url = "http://speculator.geronimo.tech:10001/spec/transform";
        $headers = "storeid-entity: magentosoap_technify-order-spec";
        $shipped_url = "http://localhost/system/public/api/shipping/endpoint";
        $order_status_update_url = $store_url."/index.php/api/soap/index?wsdl";

        if ($payload && $license_key && $store_url) {

            $order_info    = Stores::where('uuid',$license_key)->first();
            $store_id      = $order_info->technify_store_id;

            $pyload_decode = json_decode($payload,true);
            $order_object= $pyload_decode['dataPacket']['entity']['object'];
            $order_object_nifi = json_decode(Helper::curlRequestWithHeaders($nifi_url, json_encode($order_object), $headers), true);

            $payload = ['action' => 'pushOrderObject', 'timestamp' => date('y-m-d'), 'license_key' => $license_key, 'datapacket' => $order_object_nifi];

            return $payload;            
        }
    }*/

    public function index($payload,$license_key,$store_url)
    {             
        $username = "ahsans895@gmail.com";
        $password = "ahsan11";
        $nifi_url = "http://speculator.geronimo.tech:10001/spec/transform";
        $headers = "storeid-entity: magentosoap_technify-order-spec";
        $shipped_url = "http://localhost/system/public/api/shipping/endpoint";
        $order_status_update_url = $store_url."/index.php/api/soap/index?wsdl";

        if ($payload && $license_key && $store_url) {

            $order_info    = Stores::where('uuid',$license_key)->first();
            $store_id      = $order_info->technify_store_id;

            $pyload_decode = json_decode($payload,true);
            $order_object = $pyload_decode['dataPacket']['entity']['object'];      
            $transform_order_obj = json_decode(Helper::curlRequestWithHeaders($nifi_url, json_encode($order_object), $headers), true);
            if($transform_order_obj['customer']['email'] == null){
                $transform_order_obj['customer']['email'] = 'example@example.com';
            }
            $order_id      = $transform_order_obj['order_id'];
            $status  = $transform_order_obj['order_status']['status'];
            $getStoreSpec  = json_decode(Helper::getStoreSpec($store_id),true);
            $auth_username = $getStoreSpec['magentoSoapAuth']['username'];
            $auth_password = $getStoreSpec['magentoSoapAuth']['password'];
            if(isset($status) && strtolower($status)=='shipped') {

                $pushOrderObject = ['action' => 'pushOrderObject', 'timestamp' => '12-2-2014', 'license_key' => $license_key, 'datapacket' => $transform_order_obj];                
                $curl_resp = json_decode(Helper::curlRequestWithBasicAuth($shipped_url, $pushOrderObject, $username, $password),true);
                
                if($curl_resp['statusCode'] == '200'){
                   $comment = $curl_resp['dataPacket']['successMessage'];
                   $tracking_id = $curl_resp['dataPacket']['tracking_id'];
                    $client = new SoapClient($order_status_update_url);
                    $session = $client->login($auth_username, $auth_password);

                    $comment = $comment.' Tracking id '.$tracking_id;
                    $result = $client->call($session, 'technifyservices_serviceorder.update', ['payload' => compact('order_id', 'status','comment')]);

                }elseif($curl_resp['statusCode']=='500'){
                    $comment = $curl_resp['errorMessage'];
                    $tracking_id = $curl_resp['dataPacket']['tracking_id'];
                    $client = new SoapClient($order_status_update_url);
                    $session = $client->login($auth_username, $auth_password);

                    $comment = 'By technify logistics: '.$comment.' Tracking id '.$tracking_id;     
                    $result = $client->call($session, 'technifyservices_serviceorder.update', ['payload' => compact('order_id', 'status','comment')]);               
                }
                dd($result);
                return $curl_resp;
            }
        }
    }
}
