<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\requestValidationController;
use App\Models\logs;
use App\Models\OrderStatusLog;
use App\Models\Shipping;
use App\Models\Stores;
use function Couchbase\defaultDecoder;
use function GuzzleHttp\Promise\all;
use Illuminate\Http\Request;
use Helper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use function MongoDB\BSON\toJSON;

class StatsController extends Controller
{
    public function orderShipmentStatus($payload)
    {
        $action = $payload['action'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);

        if ($checkValidation['response']) {
            $datapacket = $payload['dataPacket'];
            $store_id = $datapacket['store_id'];
            $order_id = $datapacket['order_id'];
            $filter = compact('store_id', 'order_id');
            $ship_order = Shipping::select('order_id', 'status', 'order_tracking_id', 'courier_name')->where($filter)->first();


            if ($ship_order && $ship_order->status == 1) {
                $msg = ['status' => 'shipped', 'order_id' => $ship_order->order_id, 'tracking_id' => $ship_order->order_tracking_id, 'company_courier' => $ship_order->courier_name];
//                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $msg]);

                $response = Helper::ifApiSuccess($action, $msg);
            } elseif ($ship_order && $ship_order->status == 0) {
                $msg = ['status' => 'Order not shipped'];
                $response = Helper::ifApiSuccess($action, $msg);
            } else {
                $msg = 'order does not exist';
                $response = Helper::ifApiSuccess($action, $msg);
            }
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;
    }
    public function getRecentlyPlacedOrder($payload)
    {

        $action = $payload['action'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
        if ($checkValidation['response']) {
            $datapacket = $payload['dataPacket'];
            $store_id = $datapacket['store_id'];
            $filter = compact('store_id');

            $latest_order = Shipping::select('order_id', 'created_at')->where($filter)->orderby('created_at', 'desc')->first();

            if ($latest_order) {
                $msg = ['order_id' => $latest_order->order_id, 'created_at' => $latest_order->created_at];
                $response = Helper::ifApiSuccess($action, $msg);
            }
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;
    }
// ORDER STATUS LOGS
    public function getOrderStatusLogs($payload)
    {
        $action = $payload['action'];
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);

        if ($checkValidation['response']) {
            $datapacket = $payload['dataPacket'];
            $store_id = $datapacket['store_id'];
            $filter2 = compact('store_id');
            $getByStore = OrderStatusLog::select('store_id','order_id')->where($filter2)->orderby('created_at', 'asc')->get();
           $orderid = $getByStore->toArray('order_id');

            $getByStore ->select('order_status_id')->where($orderid)->get()->toArray();
            dd($getByStore);
//            $getByStore = DB::table('order_status_logs')->select('order_id')->where($filter2)->select('order_status_id')->where('order_id')->get()->toArray();
//            $getByStore = DB::table('order_status_logs')
//                ->join('stores', 'stores.id', '=', 'stores.technify_store_id')
//                ->select('stores.id', 'order_status_logs.order_status_id')
//                ->get();

            if(isset($datapacket['order_id'])) {
                $order_id = $datapacket['order_id'];
                $filter = compact('store_id', 'order_id');
                $getByOrderAndStore = OrderStatusLog::select('order_status_id')->where($filter)->get();

                if (count($getByOrderAndStore)>0) {
                 $id = ['store_id'=>$store_id , 'order_id'=>$order_id];

                    $data = $getByOrderAndStore;
                    foreach ($data as $log ) {
                        $a['logs'][] = $log->{'order_status_id'};
                    }
                    $response = Helper::ifApiSuccess($action, [$id ,  $a]);
                }
                else {
                    $msg = 'order not exist';
                    $response = Helper::ifApiSuccess($action, $msg);
                }
            }
            elseif($getByStore){

//                foreach ($getByStore as $store){
//                    $array[] = $store->{'order_status_id'};
//
//                }

                $response = Helper::ifApiSuccess($action , $getByStore);



            }
        }
        else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;

    }
}

