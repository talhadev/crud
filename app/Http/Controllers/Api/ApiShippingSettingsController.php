<?php

namespace App\Http\Controllers\Api;

use App\Models\ShippingSettings;
use App\Http\Controllers\requestValidationController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Helper;

class ApiShippingSettingsController extends Controller
{
    //
    public function mapOrderStatus($payload){
        $action = $payload['action'];


        $datapacket = $payload['dataPacket'];


        $requestController  = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);

        if ($checkValidation['response']) {
            $store_id = $datapacket['store_id'];
            $mapping_status = json_encode($datapacket['mapping_status']);

            $short_desc = $datapacket['short_desc'];
            $filter = ShippingSettings::where('store_id', $store_id)->first();
            if(count($filter)==0){
                $data = ['store_id' => $store_id, 'order_status' => $mapping_status, 'short_desc' => $short_desc];
                ShippingSettings::create($data);
                $response = Helper::ifApiSuccess($action, 'success');
            }
            else{
                $data = 'store already mapped';
                $response = Helper::ifApiSuccess($action , ['success_messege'=> $data]);

            }
        }
        else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;
    }
    public function getMapOrderStatus($payload){
        $action = $payload['action'];
        $datapacket = $payload['dataPacket'];
        $requestController  = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
        if($checkValidation['response']) {
            $store_id = $datapacket['store_id'];

            $filter = ShippingSettings::select('store_id', 'order_status', 'short_desc')->where(['store_id' => $store_id])->first();

            if (count($filter) > 0) {
                $response = Helper::ifApiSuccess($action, ['dataPacket' => ['data' => $filter]]);

            } else {
                $msg = 'No data found';
                $response = Helper::ifApiSuccess($action, ['dataPacket' => ['errorMessage' => $msg]]);
            }
//            print_r($filter);die();
//            return $filter;
        }
        else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;
    }
    public function updateMapOrderStatus($payload){
        $action = $payload['action'];
        $updateStatus = $payload['dataPacket'];
        $requestController  = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
        if($checkValidation['response']) {
            $store_id = $updateStatus['store_id'];
            $filter = ShippingSettings::where('store_id' , $store_id)->update([
                'order_status' => json_encode($updateStatus['mapping_status']),
                'short_desc' => $updateStatus['short_desc'],
            ]);
            if (count($filter)==0)
            {
                $msg = 'No data found or nothing to update';
                $response = Helper::ifApiSuccess($action, ['dataPacket'  => $msg]);
            }
            elseif (count($filter)  > 0)
            {
                $msg = 'Success';
                $response = Helper::ifApiSuccess($action ,  ['dataPacket' => ['status' => $msg]]);
            }
        }
        else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;
    }
}