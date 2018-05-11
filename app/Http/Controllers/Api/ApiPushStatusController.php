<?php

namespace App\Http\Controllers\Api;

use App\Models\logs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use App\Models\Stores;
use Config;
use App\Http\Helpers\AppHelper;
use App\Http\Controllers\requestValidationController;

class ApiPushStatusController extends Controller
{


    public function pushOrderStatus($payload)
    {
          //return $payload;
           $payload = json_decode(file_get_contents("php://input"), true);  // dd($payload);
           $license_key = $payload['license_key'];
           $action = $payload['action'];
           $store_id=Stores::select('technify_store_id')->where('uuid',$license_key)->first();
           $order_id = $payload['dataPacket']['order_id'];
           $order_status_id = $payload['dataPacket']['order_status_id'];
           $data = ['store_id'=>$store_id->technify_store_id,'order_id'=>$order_id, 'order_status_id'=>$order_status_id];
           $insert_logs=logs::create($data);

           if($insert_logs){
               $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['successMessage'=>'Logs sent']]);
               return $response;
           } else {
               $msg = 'Logs not sent';
               $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
               return $response;
           }
       }

}