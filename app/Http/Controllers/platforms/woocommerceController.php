<?php

namespace App\Http\Controllers\platforms;
use App\Http\Controllers\Controller;
use App\Models\Shipping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Helper;
use App\Models\Stores;

class woocommerceController extends Controller
{
    public function  index($payload,$license_key,$store_url){

        $username = "ahsans895@gmail.com";
        $password = "ahsan11";
        $nifi_url = "http://speculator.geronimo.tech:10001/spec/transform";
        $headers = "storeid-entity: woocomerce_technify-order-spec";
        $shipped_url ="http://localhost/system/public/api/shipping/endpoint";

        if($payload!=null){

            $order_object_nifi =json_decode(Helper::curlRequestWithHeaders($nifi_url,$payload,$headers),true);
            $order_id=$order_object_nifi['order_id'];

            $order_status=$order_object_nifi['order_status']['order_status'];
            $store_id = Stores::select('technify_store_id')->where('uuid',$license_key)->first();

            $get_store_spec = json_decode(Helper::getStoreSpec($store_id->technify_store_id),true);
            $shipping_status=$get_store_spec['shippingdetails']['variables']['courierStatusForShipped']['status'];
            $consumer_key   = $get_store_spec['shippingdetails']['variables']['restAuth']['consumer_key'];
            $consumer_secret=$get_store_spec['shippingdetails']['variables']['restAuth']['consumer_secret'];

            if(isset($order_status) && strtolower($order_status)== $shipping_status) {
                $pushOrderObject = ['action' => 'pushOrderObject', 'timestamp' => '12-2-2014', 'license_key' => $license_key, 'datapacket' => $order_object_nifi];

                $response = json_decode(Helper::curlRequestWithBasicAuth($shipped_url, $pushOrderObject, $username, $password),true);

                if($response['statusCode']=='200') {

                    $order_note_payload=urlencode($response['dataPacket']['successMessage']." "."Tracking id".$response['dataPacket']['tracking_id']);
                    Helper::woocommerceOrderNoteCurlRequest("https://www.ellisrovaski.com", $order_note_payload, "4130",$consumer_key,$consumer_secret);
                   return $response;

                }elseif($response['statusCode']=='500'){
                    $order_note_payload=urlencode($response['dataPacket']['errorMessage']);

                     Helper::woocommerceOrderNoteCurlRequest("https://www.ellisrovaski.com", $order_note_payload, "4130",$consumer_key,$consumer_secret);
                     return $response;


                }

            }
            elseif(isset($order_status) && strtolower($order_status)=='cancelled'){
                $pushOrderObject = ['action' => 'cancelShipment', 'timestamp' => '12-2-2014', 'license_key' => $license_key, 'datapacket' => $order_object_nifi];
                $response = Helper::curlRequestWithBasicAuth($shipped_url, $pushOrderObject, $username, $password);
                return $response;


            }



        }


    }
}
