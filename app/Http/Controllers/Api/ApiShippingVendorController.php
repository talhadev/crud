<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api\ApiCourierStatusController;
use App\Models\logs;
use PDF;
use SoapClient;
use Carbon\Carbon;
use App\Models\Settings;
use App\Http\Controllers\ShippingController;
use App\Http\Helpers\apphelper;
use App\Models\Orderfailure;
use App\User;
use Illuminate\Http\Request;

use Config;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiShippingController;
use App\Http\Controllers\requestValidationController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Api\Models\Orderproduct;
use \Cviebrock\EloquentSluggable\Services\SlugService;
use App\Models\Stores;
use Illuminate\Support\Str;
use Input;
use Helper;
use App\Http\Controllers\Api\vendor\ResponseController;
use App\Models\Shipping;
use phpDocumentor\Reflection\Types\Null_;
use Response;
use DB;

class ApiShippingVendorController extends Controller
{

    protected  $username;
    protected  $password;


    public function __construct()
    {
        $this->username = "ahsans895@gmail.com";
        $this->password = "ahsan11";
    }
    /*    public function actions(Request $request)
        {

            $payload = $request->payload;
            $payload_json = json_decode($payload, true);
            $action = $payload_json['action'];

            if (method_exists($this, $action)) {
                $function_name = Str::lower($action);
                $call = $this->$function_name($request);
                return $call;
            } else {
                $response = helpers::generateResponse($action, 500, false, 'Invalid action');
                return $response;
            }
        }*/

    public function successOrders($payload)
    {

        $success_orders_pyld = $payload['dataPacket'];

        $action = $payload['action'];
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);

        if( $checkValidation['response'] ) {

            $filter = ['status' => '1', 'store_id' => $success_orders_pyld['store_id']];
            $orderList = Shipping::select('id', 'order_id', 'store_id', 'courier_name', 'order_tracking_id', 'status', 'created_at', 'updated_at')->where($filter)->get();


            if (count($orderList) > 0) {
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['successOrders' => $orderList]]);
            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

    public function failedOrders($payload)
    {
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);

        $failed_orders_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $store_id = $failed_orders_pyld['store_id'];
        $filter = ['store_id' => $store_id, 'status' => '0'];
        $failure_order = Orderfailure::select('id', 'order_id', 'store_id', 'failure_address', 'failure_city', 'telephone', 'email', 'status')->where($filter)->get();

        if( $checkValidation['response'] ) {

            if (count($failure_order) > 0) {

                $store_name = Helper::GetStoreNameByIDOrEmail($store_id);

                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['Store' => $store_name,'failureOrders' => $failure_order]]);

            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }

        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

    public function cancelledOrders($payload)
    {
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
        $cancelled_orders_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $store_id = $cancelled_orders_pyld['store_id'];
        $filter = ['store_id' => $store_id, 'status' => '2'];
        $cancelled_orders = Shipping::select('store_id', 'order_id', 'courier_name', 'order_tracking_id', 'status', 'created_at')->where($filter)->get();

        if( $checkValidation['response'] ) {
            if (count($cancelled_orders) > 0) {

                $store_name = Helper::GetStoreNameByIDOrEmail($store_id);

                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['storeName' => $store_name,'cancelledOrders' => $cancelled_orders]]);

            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }

        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }


    public function allProductsInfo($payload)
    {

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
        $all_product_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $store_id = $all_product_pyld['store_id'];
        $response_array=array();
        if ($checkValidation['response']) {

            $all_products = DB::table('shippings')->select('orderinfo','created_at')->where('store_id',$store_id)
                ->where(function ($query) use ($payload) {

                    $order_data = $payload;
                    if ($order_data['dataPacket']['month'] != '') {
                        $query->whereMonth('created_at', $order_data['dataPacket']['month']);

                    }
                })->where(function ($query)  use ($payload){
                    $order_data = $payload;
                    if ($order_data['dataPacket']['day'] != '') {
                        $query->whereDay('created_at', $order_data['dataPacket']['day']);

                    }
                })->where(function ($query)  use ($payload){
                    $order_data = $payload;
                    if ($order_data['dataPacket']['year'] != '') {
                        $query->whereYear('created_at', $order_data['dataPacket']['year']);

                    }
                })->get();

               foreach($all_products as $product){
                   $orderinfo = json_decode($product->orderinfo,true);
                   $cartinfo  =  $orderinfo['datapacket']['cart'];

                   array_push($response_array, $cartinfo);

               }

            if (count($all_products) > 0) {

                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => [$response_array]]);
                return $response;
            } else {
                $msg = "No data found";
                $response = array_merge(Helper::constantResponse($action, 500, ''), ['dataPacket' => ['errorMessage' => $msg]]);
            }

        }else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
            return $response;
        }



    }
    public function updateOrder($payload)
    {
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);
        $update_order_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        if( $checkValidation['response'] ) {
            $orderList = Orderfailure::where('store_id', $update_order_pyld['store_id'])->where('order_id', $update_order_pyld['order_id'])
                ->update([
                    'failure_address' => $update_order_pyld['address'],
                    'failure_city' => $update_order_pyld['city'],
                    'telephone' => $update_order_pyld['phone'],
                ]);

            if ($orderList == false) {
                $msg = 'No data found or nothing to update';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            } elseif ($orderList == true) {
                $msg = 'Success';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['status' => $msg]]);
            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }
        }
        else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

    public function getFailedOrderDetail($payload)
    {
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);
        $get_failed_order_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $filter1 = ['store_id'=> $get_failed_order_pyld['store_id']];
        $filter2 = ['order_id'=> $get_failed_order_pyld['order_id']];
        $get_failed_orders = Orderfailure::select('store_id','order_id','failure_city','failure_address','telephone','status')->where($filter1)->where($filter2)->get();

        if( $checkValidation['response'] ) {
            if (count($get_failed_orders) > 0) {
                $store_name = Helper::GetStoreNameByIDOrEmail($get_failed_order_pyld['store_id']);
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['storeName' => $store_name,'failedOrderDetails' => $get_failed_orders]]);
            }else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }}else {
            $response = Helper::ifValidationFalse($action, $checkValidation);}
        return $response;}



    public function updateAndproceed($payload)
    {
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);
        $update_and_proceed_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        if( $checkValidation['response'] ) {

            $check_status = orderStatus($update_and_proceed_pyld['store_id'], $update_and_proceed_pyld['order_id']);

            if ($check_status['status'] == '0') {
                $orderList = Orderfailure::where('store_id', $update_and_proceed_pyld['store_id'])->where('order_id', $update_and_proceed_pyld['order_id'])
                    ->update([
                        'failure_address' => $update_and_proceed_pyld['address'],
                        'failure_city'    => $update_and_proceed_pyld['city'],
                        'telephone'       => $update_and_proceed_pyld['phone'],
                    ]);

                if ($orderList == true) {

                    if ($update_and_proceed_pyld['order_id']) {
                        $failure_order_ids = $update_and_proceed_pyld['order_id'];

                    } else {
                        $failure_order_ids = array_slice(explode(',', implode(',', $request->all())), 1);
                    }
                    $get_failure_order = Orderfailure::where('order_id', $failure_order_ids)->first();

                    $store_id = $get_failure_order->store_id;

                    $address = $get_failure_order->failure_address;
                    $city = $get_failure_order->failure_city;
                    $telephone = $get_failure_order->telephone;

                    $json = json_decode($get_failure_order->orderinfo);
                    $json->payload = $json->datapacket;
                    unset($json->datapacket);

                    $json->payload->shipping_address->address = $address;
                    $json->payload->shipping_address->city = $city;
                    $json->payload->customer->telephone = $telephone;

                    $url = Config::get('urls.curl_urls.curl_api_shipped_request');
                    $useragent = 'Send request order failure';
                    $store_info = Stores::Select('uuid')->where('technify_store_id', $update_and_proceed_pyld['store_id'])->first();
                    $license_key = $store_info->uuid;
                    $payload = ['action'=>'pushOrderObject', 'timestamp'=>Carbon::now()->toDayDateTimeString(),'license_key'=>$license_key,'datapacket'=>$json->payload];

//                    $shipping = new  ApiShippingController();
//                    $response = $shipping->shipUpdatedOrder(json_encode($payload,true));
                    $response   = json_decode(Helper::curlRequestWithBasicAuth($url, $payload, $this->username, $this->password),true);


                    if ($response['statusCode'] == 200) {

                        $update_faliure_order = Orderfailure::where('order_id', $update_and_proceed_pyld['order_id']);
                        $data = ['status' => '1', 'orderinfo' => json_encode($json->payload)];
                        $update_faliure_order->update($data);
                        $msg = 'Order Update and Proceeded';

                        $response = array_merge(Helper::constantResponse($action, 200, $msg), ['dataPacket' => ['message' => $msg]]);
                        //$result = helpers::generateResponse($payload_json['action'], 200, true, 'Order Update and Proceeded');
                        return $response;
                    }
                    $msg = 'Could not update and proceed';
                    $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                    //$result = helpers::generateResponse($payload_json['action'], 500, false, 'Could not update and proceed');
                    return $response;
                }

            }else {
                $msg = 'Order already proceeded';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                //  $result =helpers::generateResponse($payload_json['action'], 500, false, 'Order already proceeded');
                return $response;
            }
        }else {
            $response = Helper::ifValidationFalse($action, $checkValidation);}
        return $response;

    }

    public function proceed($payload)
    {

        $proceed_order_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $validator = Validator::make(
            array(
                'store_id'   => $proceed_order_pyld['store_id'],
                'order_id'   => $proceed_order_pyld['order_id']),
            array(
                'store_id'   => 'required|integer',
                'order_id'   => 'required|array|',
                'order_id.*' => 'integer')
        );
        if ($validator->fails()) {
            // $error_messages = $validator->messages()->all();
            //$response = helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
            // return $response;
        } else {
            // $check_status = orderStatusForMultipleIds($payload_json['dataPacket']['store_id'], $payload_json['dataPacket']['order_id']);

            if ($proceed_order_pyld['order_id']) {
                $failure_order_ids = $proceed_order_pyld['order_id'];
            }else {
                $failure_order_ids = array_slice(explode(',', implode(',', $request->all())), 1);

            }
            $success_responses = 0;
            foreach ($failure_order_ids as $order_id) {

                $get_failure_order = Orderfailure::where('order_id', $order_id)->first();
                $store_id  = $get_failure_order->store_id;
                $address   = $get_failure_order->failure_address;
                $city      = $get_failure_order->failure_city;
                $telephone = $get_failure_order->telephone;

                $json          = json_decode($get_failure_order->orderinfo);
                $json->payload = $json->datapacket;
                unset($json->datapacket);

                $json->payload->shipping_address->address = $address;
                $json->payload->shipping_address->city = $city;
                $json->payload->customer->telephone = $telephone;
                $url = Config::get('urls.curl_urls.curl_api_shipped_request');

                $payload = ['action'=>'shipped', 'timestamp'=>Carbon::now()->toDayDateTimeString(), 'dataPacket'=>$json->payload];
                $useragent  = 'Send request order failure';
                $response   = json_decode(Helper::curlRequestWithBasicAuth($url, $payload, $this->username, $this->password),true);

                if ($response['statusCode'] == "200") {

                    $success_responses++;
                }
            }
            if ($success_responses    == count($failure_order_ids)) {
                $update_faliure_order = Orderfailure::whereIn('order_id', $failure_order_ids);
                $data                 = ['status' => '1', 'orderinfo' => json_encode($json->payload)];
                $update_faliure_order->update($data);
                $msg                  = 'All orders Proceeded';
                $response             = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                // $result = helpers::generateResponse($payload_json['action'], 200, true, 'All orders Proceeded');
                // return $result;
            } else {
                $msg      = 'Could not proceed all orders';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                //  $result = helpers::generateResponse($payload_json['action'], 500, false, 'Could not proceed all orders');
                //  return $result;
            }

        }
        return $response;
    }

    public function airwayBill($payload){

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);
        $airway_bill_pyld = $payload['dataPacket'];
        $store_id = $airway_bill_pyld['store_id'];
        $order_id = $airway_bill_pyld['order_id'];
        $action = $payload['action'];

        if( $checkValidation['response'] ){
            $response_data = array();
            $courier_data = Shipping::where('store_id', $airway_bill_pyld['store_id'])->whereIn('order_id', $airway_bill_pyld['order_id'])->get();

            $courier_data = Shipping::where(compact('store_id', 'order_id'))->first();

            if (count($courier_data) > 0) {
                foreach ($courier_data as $data) {

                    if ($data->courier_name == Config::get('courier_names.couriers.TCS')) {

                        $view_data = Helper::GetCNDetailsByReferenceNumberTCS($data->order_id);
                        $response = helpers::generateResponse($action, 200, true, $view_data);

                    }elseif ($data->courier_name == "kangaroo") {

                        $view_data = Helper::GetCNDetailsByOrderIdKangroo($data->order_id);
                        $view = \View::make('airwaybill.kangaroo')->with($view_data['trackParsal']);

                        return $view;
                    }elseif ($data->courier_name == "tcs") {
                        $view_data = Helper::GetCNDetailsByReferenceNumberTCS($data->order_id);
                        $view = \View::make('airwaybill.tcs')->with($view_data);

//                      $pdf= PDF::loadHTML($view)->setPaper('a4')->setOrientation('landscape')->setOption('margin-bottom',0);
//
//                        return $pdf->download();

                        return $view;

                    } elseif ($data->courier_name == "leopard") {
                        $data = Helper::GetCNDetailsByReferenceNumberLeopard($data->order_tracking_id);

                      //  $view = \View::make('airwaybill.leopard')->with($data);
                        ///$pdf= PDF::loadHTML($view)->setPaper('a4')->setOrientation('landscape')->setOption('margin-bottom', 0);

                       // return $pdf->download();

                        //return $view;
                        //  $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);


                    } elseif ($data->courier_name == Config::get('courier_names.couriers.BlueEx')) {

                        $data = Helper::GetCNDetailsByReferenceNumberBlueEx();

                        $view = \View::make('blueex.airwayBill');
                        return $view;

                        //$response = helpers::generateResponse($payload_json['action'], 200, true, $$response_data);


                    }
                    elseif ($data->courier_name == 'callcourier') {


                        $data = Helper::GetCNDetailsByReferenceNumberCallCourier($data->order_tracking_id);
                         $order_data=Shipping::select('orderinfo')->where('store_id', $airway_bill_pyld['store_id'])->where('order_id', $airway_bill_pyld['order_id'])->first();
                         $order_info= json_decode($order_data->orderinfo, true);
                        $data_and_time = explode(" ", $order_info['datapacket']['orderinfo']['date_added']);
                        $date= $data_and_time[0]; // piece1
                        $time = $data_and_time[1]; // piece2
                        $service = $order_info['datapacket']['payment_method'][0]['code'];
                        $pieces  = Helper::getPieces($order_info['datapacket']['cart']);
                        $view = \View::make('airwaybill.callCourier')->with('data', ['courier_data' => $data[0], 'order_data' => $order_info['datapacket'],'date'=>$date, 'time'=>$time, 'service'=>$service,'pieces'=>$pieces]);
                        return $view;

                        //$response = helpers::generateResponse($payload_json['action'], 200, true, $$response_data);

                    }
                }

            }else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                return $response;
            }

        }else {

            $response = Helper::ifValidationFalse($action, $checkValidation);
            return $response;
        }

    }
    public function GetOrderStatusLeopard($payload){

        //   dd($tracking_id);
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'http://new.leopardscod.com/webservice/trackBookedPacket/format/json/');  // Write here Test or Production Link
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, array(
            'api_key' => 'BC8A745A2B7DA612EFA7E26B96E8E829',
            'api_password' => 'A?(.>H5WL2MF9GU',
            'track_numbers' =>'KI752204647'             // E.g. 'XXYYYYYYYY' OR 'XXYYYYYYYY,XXYYYYYYYY,XXYYYYYY' 10 Digits each number
        ));

        $buffer = curl_exec($curl_handle);
        curl_close($curl_handle);
        $result = json_decode($buffer, true);

        if($result['packet_list']!=null) {
            $table = end($result['packet_list']);
            if($table['Tracking Detail']!=null) {
                $orderStatus = $table['Tracking Detail'];
                $finalStatus = reset($orderStatus);
                dd($finalStatus);
                return $finalStatus['Status'];
            }else{
                return null;
            }
        }else{
            return null;
        }


    }

    public function logicsOrder($payload){

        $logics_order_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
        $store_id = $logics_order_pyld['store_id'];
        if($checkValidation['response']) {
            $orderList = DB::table('shippings')->select('order_id', 'courier_name', 'status', 'created_at')->where('courier_name', $logics_order_pyld['courier_name'])
                ->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if($order_data['dataPacket']['store_id']  != ''){
                        $query->where('store_id', $order_data['dataPacket']['store_id']);
                    }
                })->where(function ($query)  use ($payload){

                    global $request;
                    $payload = $request->payload;
                    $order_data = $payload;
                    if ($order_data['dataPacket']['month'] != '') {
                        $query->whereMonth('MONTH(created_at)', $order_data['dataPacket']['month']);

                    }
                })->get();

            if (count($orderList) > 0) {
                $store_name = Helper::GetStoreNameByIDOrEmail($logics_order_pyld['store_id']);
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store_name'=>$store_name,'logicsOrder' => $orderList]]);
            } else {
                $msg = "No data found";
                $response = array_merge(Helper::constantResponse($action, 500, ''), ['dataPacket' => ['errorMessage' => $msg]]);
            }}else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

    public function shippingStats($payload){

        $action = $payload['action'];
        $datapacket = $payload['dataPacket'];
        $store_id = $datapacket['store_id'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
                                
        if( $checkValidation['response'] ) {

            $getCitiesTopFiveMonthly = $this->getCitiesTopFiveMonthly($store_id);
            $getTopFiveAllTimeCities = $this->getCitiesTopFiveAllTime($store_id);
            
            $filter = compact('store_id');
            $mnth_fltr = 'MONTH(created_at) = MONTH(NOW())';
            $tcs = ['courier_name' => 'tcs'];
            $leopard = ['courier_name' => 'leopard'];
            $kangaroo = ['courier_name' => 'kangaroo'];
            $bluex = ['courier_name' => 'bluex'];
            $callcourier = ['courier_name' => 'callcourier'];

            $all_data = Shipping::where($filter)->where('status', 1)->get();
            $month_data = Shipping::where($filter)->whereRaw($mnth_fltr)->where('status', 1)->get();

            $mnthly_shipped_orders = Shipping::where($filter)->whereRaw($mnth_fltr)->count();
            $mnthly_kangaroo_orders = Shipping::where($filter)->whereRaw($mnth_fltr)->where($kangaroo)->count();
            $mnthly_leopard_orders = Shipping::where($filter)->whereRaw($mnth_fltr)->where($leopard)->count();
            $mnthly_tcs_orders = Shipping::where($filter)->whereRaw($mnth_fltr)->where($tcs)->count();
            $mnthly_bluex_orders = Shipping::where ($filter)->whereRaw($mnth_fltr)->where($bluex)->count();
            $mnthly_callcourier_orders = Shipping::where ($filter)->whereRaw($mnth_fltr)->where($callcourier)->count();

            $total_shipped_orders = Shipping::where($filter)->count();
            $total_kangaroo_orders = Shipping::where($filter)->where($kangaroo)->count();
            $total_leopard_orders = Shipping::where($filter)->where($leopard)->count();
            $total_tcs_orders = Shipping::where($filter)->where($tcs)->count(); 
            $total_bluex_orders = Shipping::where($filter)->where($bluex)->count();
            $total_callcourier_orders = Shipping::where($filter)->where($callcourier)->count();

            $mnthly_kangaroo_delivered = Shipping::where($filter)->whereRaw($mnth_fltr)->where($kangaroo)->where('courier_status', 'Delivered')->count();
            $mnthly_leopard_delivered = Shipping::where($filter)->whereRaw($mnth_fltr)->where($leopard)->where('courier_status', 'Delivered')->count();
            $mnthly_tcs_delivered = Shipping::where($filter)->whereRaw($mnth_fltr)->where($tcs)->where('courier_status', 'Delivered')->count();
            $mnthly_bluex_delivered = Shipping::where($filter)->whereRaw($mnth_fltr)->where($bluex)->where('courier_status', 'Delivered')->count();

            $mnthly_kangaroo_cancelled = Shipping::where($filter)->whereRaw($mnth_fltr)->where($kangaroo)->where('courier_status', 'Cancelled')->count();
            $mnthly_leopard_cancelled = Shipping::where($filter)->whereRaw($mnth_fltr)->where($leopard)->where('courier_status', 'Cancelled')->count();
            $mnthly_bluex_cancelled   = Shipping::where($filter)->whereRaw($mnth_fltr)->where($bluex)->where('courier_status', 'Return to Shipper')->count();
            $mnthly_leopard_returned  = Shipping::where($filter)->whereRaw($mnth_fltr)->where($leopard)->where('courier_status', 'Return to Sender')->count();

            $total_kangaroo_cancelled_alltime = Shipping::where ($filter)->where($kangaroo)->where('courier_status', 'Cancelled')->count();
            $total_leopard_cancelled_alltime = Shipping::where($filter)->where($leopard)->where('courier_status', 'Cancelled')->count();
            $total_bluex_cancelled_alltime = Shipping::where($filter)->where($bluex)->where('courier_status', 'Return to Shipper')->count();
            $total_leopard_returned_alltime = Shipping::where($filter)->where($leopard)->where('courier_status', 'Return to Sender')->count();
            
            $total_kangaroo_delivered_alltime = Shipping::where($filter)->where($kangaroo)->where('courier_status', 'Delivered')->count();
            $total_leopard_delivered_alltime = Shipping::where($filter)->where($leopard)->where('courier_status', 'Delivered')->count();
            $total_tcs_delivered_alltime = Shipping::where($filter)->where($tcs)->where('courier_status', 'Delivered')->count();
            $total_bluex_delivered_alltime = Shipping::where($filter)->where($bluex)->where('courier_status', 'Delivered')->count();
            
            $delivery_time_kangaroo = Settings::select('est_delivery')->where($kangaroo)->first();
            $delivery_time_leopard = Settings::select('est_delivery')->where($leopard)->first();
            $delivery_time_tcs = Settings::select('est_delivery')->where($tcs)->first();

            $getOrdersDetailsMonthly = $this->getOrderDetailsMonthly($store_id, $mnthly_leopard_delivered, $mnthly_kangaroo_delivered, $mnthly_tcs_delivered, $mnthly_bluex_delivered, $mnthly_kangaroo_orders, $mnthly_leopard_orders, $mnthly_tcs_orders, $mnthly_bluex_orders, $mnthly_bluex_cancelled, $mnthly_leopard_cancelled, $mnthly_kangaroo_cancelled, $mnthly_leopard_returned);

            $getOrdersDetailsAllTime = $this->getOrderDetailsAllTime($store_id, $total_leopard_delivered_alltime, $total_kangaroo_delivered_alltime, $total_tcs_delivered_alltime, $total_bluex_delivered_alltime, $total_kangaroo_orders, $total_leopard_orders, $total_tcs_orders, $total_bluex_orders, $total_bluex_cancelled_alltime, $total_leopard_cancelled_alltime, $total_kangaroo_cancelled_alltime, $total_leopard_returned_alltime, $total_tcs_orders);

            $current_month = [
                'period' => 'currentMonth',
                'monthly_shipped_orders' => $mnthly_shipped_orders,
                'total_value' => $this->getTotalShipmentValue($month_data),
                'mnthly_orders' => [
                    'tcs' => $mnthly_tcs_orders,
                    'kangaroo' => $mnthly_kangaroo_orders,
                    'leopard' => $mnthly_leopard_orders,
                    'bluex' => $mnthly_bluex_orders,
                    'call_couier' => $mnthly_callcourier_orders
                ],
                'citiesStats' => [$getCitiesTopFiveMonthly],
                'courier_details'=>[$getOrdersDetailsMonthly]
            ];

            $allData = [
                'period' => 'allData',
                'total_shipped_orders' => $total_shipped_orders,
                'total_value' => $this->getTotalShipmentValue($all_data),
                'total_orders' => [
                    'tcs' => $total_tcs_orders,
                    'kangaroo' => $total_kangaroo_orders,
                    'leopard' => $total_leopard_orders,
                    'call_courier'=>$total_callcourier_orders,
                    'bluex'=>$total_bluex_orders],

                'citiesStats' => [
                    $getTopFiveAllTimeCities
                ],
               'courier_details'=>[
                   $getOrdersDetailsAllTime

               ]
            ];
            
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['currentMonth' => $current_month, 'allTime' => $allData,]]);

        }else{
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

    public function shippingStats1($payload){

        $response_data                   = array();
        $response_data2                  = array();
        $shipping_stats_pyld             = $payload['dataPacket'];

        $getCitiesTopFiveMonthly         = $this->getCitiesTopFiveMonthly($shipping_stats_pyld['store_id']);
        $getTopFiveAllTimeCities         = $this->getCitiesTopFiveAllTime($shipping_stats_pyld['store_id']);
        $action                          = $payload['action'];

        $requestController               = new requestValidationController();
        $checkValidation                 = $requestController->validateStore($payload);
        if($checkValidation['response']==true) {

            $month_data                   = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('status', 1)->get();
            $all_data                     = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->get();
            $totalTcsOrders1              = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'tcs')->count();
            $totalKangarooOrders1         = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'kangaroo')->count();
            $totalLeopardOrders1          = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'leopard')->count();
            $totalShippedOrders1          = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->count();
            $totalcallCourier1            = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'callCourier')->count();
            $totalBluex1                  = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'bluex')->count();

            $totalBluexOrders             = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'bluex')->count();
            $totalShippedOrders           = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->count();
            $totalTcsOrders               = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'tcs')->count();
            $totalKangarooOrders          = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'kangaroo')->count();
            $totalCallCourierOrders       = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'callCourier')->count();
            $totalLeopardOrders           = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'leopard')->count();
            $totalBluexOrders             = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'bluex')->count();
            $totalShippedOrders           = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->count();
            $totaLeopardDeliveredMonthly  = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'leopard')->where('courier_status', 'Delivered')->count();
            $totaTcsDeliveredMonthly      = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'tcs')->where('courier_status', 'Delivered')->count();
            $totaKangarooDeliveredMonthly = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'kangaroo')->where('courier_status', 'Delivered')->count();
            $totaBluexDeliveredMonthly = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'bluex')->where('courier_status', 'Delivered')->count();
            $totalLeopardCancelledMonthly = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'leopard')->where('courier_status', 'Cancelled')->count();
            $totalLeopardReturnedMonthly  = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'leopard')->where('courier_status', 'Return to Sender')->count();
            $totalKangarooCancelledMonthly= Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'kangaroo')->where('courier_status', 'Cancelled')->count();
            $totalBluexCancelledMonthly   = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'bluex')->where('courier_status', 'Return to Shipper')->count();

            $totalLeopardCancelledAlltime = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'leopard')->where('courier_status', 'Cancelled')->count();
            $totalLeopardReturnedAlltime  = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'leopard')->where('courier_status', 'Return to Sender')->count();
            $totalKangarooCancelledAlltime= Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'kangaroo')->where('courier_status', 'Cancelled')->count();

            $totaKangarooDeliveredMonthly = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name', 'bluex')->where('courier_status', 'Cancelled')->count();
            $totalLeopardDeliveredAlltime = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'leopard')->where('courier_status','Delivered')->count();
            $totalTcsDeliveredAlltime     = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'tcs')->where('courier_status','Delivered')->count();
            $totalKangarooDeliveredAlltime= Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'kangaroo')->where('courier_status','Delivered')->count();
            $totalBluexDeliveredAlltime= Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'bluex')->where('courier_status','Delivered')->count();
            $totalBluexCancelledAlltime = Shipping::where ('store_id', $shipping_stats_pyld['store_id'])->where('courier_name', 'bluex')->where('courier_status', 'Return to Shipper')->count();

            $delivery_time_kangaroo       = Settings::select('est_delivery')->where('courier_name', 'kangaroo')->first();
            $delivery_time_tcs            = Settings::select('est_delivery')->where('courier_name', 'tcs')->first();
            $delivery_time_leopard        = Settings::select('est_delivery')->where('courier_name', 'leopard')->first();
           // $decode_delivery_time_tcs     = json_decode($delivery_time_tcs->est_delivery,true);
           // $decode_delivery_time_leopard = json_decode($delivery_time_leopard->est_delivery,true);
          //  $decode_delivery_time_kangaroo= json_decode($delivery_time_kangaroo->est_delivery,true);
            $getOrdersDetailsMonthly= $this->getOrderDetailsMonthly($shipping_stats_pyld['store_id'],$totaLeopardDeliveredMonthly,$totaKangarooDeliveredMonthly,$totalLeopardReturnedMonthly,$totalBluex1,$totalBluexCancelledMonthly,$totalKangarooOrders1,$totalLeopardCancelledMonthly,$totalLeopardOrders1,$totalKangarooCancelledMonthly,$totaBluexDeliveredMonthly);
            $getOrdersDetailsAllTime= $this->getOrderDetailsAllTime($shipping_stats_pyld['store_id'],$totalLeopardDeliveredAlltime,$totalLeopardReturnedAlltime,$totalKangarooDeliveredAlltime,$totalBluexOrders,$totalBluexCancelledAlltime,$totalKangarooOrders,$totalLeopardCancelledAlltime,$totalLeopardOrders,$totalKangarooCancelledAlltime,$totalBluexDeliveredAlltime);
            $current_month = [
                'period' => 'currentMonth',
                'total_shipped_orders' => $totalShippedOrders1,
                'total_value' => $this->getTotalShipmentValue($month_data),
                'total_orders' => [
                    'tcs' => $totalTcsOrders1,
                   'kangaroo' => $totalKangarooOrders1,
                    'leopard' => $totalLeopardOrders1,
                    'call_couier'=>$totalcallCourier1,
                    'bluex'      =>$totalBluex1 ],
                'citiesStats' => [
                    $getCitiesTopFiveMonthly],
                'order_details'=>[

                ],
                'order_details'=>[
                    $getOrdersDetailsMonthly

                ]

            ];


            $allData = [
                'period' => 'allData',
                'total_shipped_orders' => $totalShippedOrders,
                'total_value' => $this->getTotalShipmentValue($all_data),
                'total_orders' => [
                    'tcs' => $totalTcsOrders,
                   'kangaroo' => $totalKangarooOrders,
                    'leopard' => $totalLeopardOrders,
                    'call_courier'=>$totalCallCourierOrders,
                    'bluex'=>$totalBluexOrders],

                'citiesStats' => [
                    $getTopFiveAllTimeCities
                ],
               'order_details'=>[
                   $getOrdersDetailsAllTime

               ]
            ];

            array_push($response_data, $current_month);
            array_push($response_data2, $allData);
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['currentMonth' => $response_data,'allTime' => $response_data2,]]);

        }else{
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

    public function getOrderDetailsMonthly($store_id, $mnthly_leopard_delivered, $mnthly_kangaroo_delivered, $mnthly_tcs_delivered, $mnthly_bluex_delivered, $mnthly_kangaroo_orders, $mnthly_leopard_orders, $mnthly_tcs_orders, $mnthly_bluex_orders, $mnthly_bluex_cancelled, $mnthly_leopard_cancelled, $mnthly_kangaroo_cancelled, $mnthly_leopard_returned)
    {        
        $courier_names = Shipping::where(compact('store_id'))->distinct()->get(['courier_name']);        
        
        foreach ( $courier_names as $name ) {
            
            $response[$name->courier_name] = [
                'orders_booked'     => isset(${'mnthly_'.$name->courier_name.'_orders'}) ? ${'mnthly_'.$name->courier_name.'_orders'} : 0,

                'orders_delivered'  => isset(${'mnthly_'.$name->courier_name.'_delivered'}) ? ${'mnthly_'.$name->courier_name.'_delivered'} : 0,

                'order_cancelled'   => isset(${'mnthly_'.$name->courier_name.'_cancelled'}) ? ${'mnthly_'.$name->courier_name.'_cancelled'} : 0,

                'order_return'      => isset(${'mnthly_'.$name->courier_name.'_return'}) ? ${'mnthly_'.$name->courier_name.'_return'} : 0,

                'avg_delivery_time' => '3 days'
            ];
        }

        return $response;
    }    

    public function getOrderDetailsAllTime($store_id, $total_leopard_delivered_alltime, $total_kangaroo_delivered_alltime, $total_tcs_delivered_alltime, $total_bluex_delivered_alltime, $total_kangaroo_orders, $total_leopard_orders, $total_tcs_orders, $total_bluex_orders, $total_bluex_cancelled_alltime, $total_leopard_cancelled_alltime, $total_kangaroo_cancelled_alltime, $total_leopard_returned_alltime)
    {
        $courier_names = Shipping::where(compact('store_id'))->distinct()->get(['courier_name']);        
        
        foreach ( $courier_names as $name ) {
            
            $response[$name->courier_name] = [
                'orders_booked'     => isset(${'total_'.$name->courier_name.'_orders'}) ? ${'total_'.$name->courier_name.'_orders'} : 0,

                'orders_delivered'  => isset(${'total_'.$name->courier_name.'_delivered_alltime'}) ? ${'total_'.$name->courier_name.'_delivered_alltime'} : 0,

                'order_cancelled'   => isset(${'total_'.$name->courier_name.'_cancelled_alltime'}) ? ${'total_'.$name->courier_name.'_cancelled_alltime'} : 0,

                'order_return'      => isset(${'total_'.$name->courier_name.'_return_alltime'}) ? ${'total_'.$name->courier_name.'_return_alltime'} : 0,
                
                'avg_delivery_time' => '3 days'
            ];
        }

        return $response;
    }

    public function getCitiesTopFiveAllTime($store_id){

        $result        = array();
        $final_total   = array();
        $all_amounts   = array();
        $test          = array();
        $top_five_stats=array();

        $json = json_decode($this->getStoreSpec($store_id), true);
        $orderinfo = Shipping::where(compact('store_id'))->get();

        foreach( $orderinfo as $data ) {

            $json = json_decode($data->orderinfo, true);            
            $order_amount = $json['datapacket']['total'];
            $total_amount = Helper::getOrderTotalAmount($order_amount);
            
            if ( isset($json['ifCityIsNull']) ) {
                $cities_data = strtolower($json['ifCityIsNull']);                
                $amount = [
                    'City'   => strtolower($cities_data), 
                    'amount' => $total_amount
                ];
                array_push($all_amounts, $amount);
                array_push($result, $cities_data);
            } elseif ( $json['datapacket']['shipping_address']['city'] != "null" ) {
                $cities_data = strtolower($json['datapacket']['shipping_address']['city']);                
                $amount = [
                        'City'   => strtolower($cities_data),
                        'amount' => $total_amount
                    ];
                array_push($all_amounts, $amount);
                array_push($result, $cities_data);
            } elseif ( $json['datapacket']['shipping_address']['address'] != "null" ) {

                $get_store_spec = json_decode($this->getStoreSpec($store_id), true);
                if(isset($get_store_spec['shippingdetails'])) {
                    $cities = $get_store_spec['shippingdetails']['variables']['location'];

                    foreach ($cities as $key => $value) {
                        if(strpos(strtolower($json['datapacket']['shipping_address']['address']),$value['cities'][0])==true) {
                            $cities_data = [strtolower($value['cities'][0])];
                            $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                            array_push($all_amounts, $amount);
                            array_push($result, $cities_data[0]);
                        }elseif($value['cities'][0]=='else'){
                            $city_list = array_map('strtolower', include("cities/".$store_id.".php"));
                            foreach ($city_list as $key=>$value){

                                if(strpos(strtolower($json['datapacket']['shipping_address']['address']),$value)){
                                    $cities_data = [strtolower($value)];
                                    $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                                    array_push($all_amounts, $amount);
                                    array_push($result, $cities_data[0]);
                                }
                            }
                        }
                    }
                }
            }
        }
        $sum = array_reduce($all_amounts, function ($a, $b) {
            isset($a[$b['City']]) ? $a[$b['City']]['amount'] += $b['amount'] : $a[$b['City']] = $b;
            return $a;
        });
        $cities_count = array_count_values($result);
        $other_cities = array_slice($final_total,  6);
        arsort($cities_count);

        foreach($cities_count as $word => $count)
        {
            $response=[
                'city'=> $word,
                'total_shipped'=>$count,
                'total_order_value'=>$sum[$word]['amount']
            ];
            array_push($final_total,$response);
        }
        $top_five = array_slice($final_total, 0, 5, true);
        array_push($top_five_stats, $top_five);
        $other_cities = array_slice($final_total,  5);

        $total_shipped=0;
        $total_amount=0;
        $total_order_value=0;
        foreach($other_cities as $key=>$value){

            //$total_shipped =$total_shipped +$key;
            $total_shipped=$total_shipped+$value['total_shipped'];

            $total_order_value=$total_order_value+$value['total_order_value'];

        }
        if( $store_id == '100005' ) {
            
            $other_cities_stats = [
                'city' => 'others',
                'total_shipped' => $total_shipped-1,
                'total_order_value' => $total_order_value
            ];
        }else{
            $other_cities_stats = [
                'city' => 'others',
                'total_shipped' => $total_shipped,
                'total_order_value' => $total_order_value
            ];

        }

        //$top_five_stats[0][6]=$other_cities_stats;
        array_push($top_five_stats, $other_cities_stats);
        return $top_five_stats;
    }

    public function getCitiesTopFiveMonthly($store_id){

        $mnth_fltr = 'MONTH(created_at) = MONTH(NOW())';
        $result      = array();
        $final_total = array();
        $all_amounts = array();
        $top_five_stats = array();
        $json        = json_decode($this->getStoreSpec($store_id), true);
        $orderinfo   = Shipping::select('orderinfo')->where(compact('store_id'))->whereRaw($mnth_fltr)->get();
        
        if( count($orderinfo) > 0 ) {

            foreach ($orderinfo as $data) {

                $json = json_decode($data->orderinfo, true);
                $order_amount = $json['datapacket']['total'];
                $total_amount = Helper::getOrderTotalAmount($order_amount);

                if( isset($json['datapacket']['orderinfo']['order_id']) ) {
                    $order_id = $json['datapacket']['orderinfo']['order_id'];
                }
                elseif( isset($json['datapacket']['order_id']) ){
                    $order_id = $json['datapacket']['order_id'];
                }

                if (isset($json['ifCityIsNull'])) {

                    $cities_data = [strtolower($json['ifCityIsNull'])];
                    $amount = ['City' => strtolower($cities_data[0]), 'amount' => $total_amount];
                    array_push($all_amounts, $amount);
                    array_push($result, $cities_data[0]);

                } elseif ($json['datapacket']['shipping_address']['city'] != "null") {
                    $cities_data = [strtolower($json['datapacket']['shipping_address']['city'])
                    ];
                    $amount = ['City' => strtolower($cities_data[0]), 'amount' => $total_amount];
                    array_push($all_amounts, $amount);
                    array_push($result, $cities_data[0]);

                } elseif ($json['datapacket']['shipping_address']['address'] != "null") {
                    $get_store_spec = json_decode($this->getStoreSpec($store_id), true);

                    if (isset($get_store_spec['shippingdetails'])) {
                        $cities = $get_store_spec['shippingdetails']['variables']['location'];

                        foreach ($cities as $key => $value) {
                            if (strpos(strtolower($json['datapacket']['shipping_address']['address']), $value['cities'][0]) == true) {
                                $cities_data = [strtolower($value['cities'][0])];
                                $amount = ['City' => strtolower($cities_data[0]), 'amount' => $total_amount];
                                array_push($all_amounts, $amount);
                                array_push($result, $cities_data[0]);


                            } elseif ($value['cities'][0] == 'else') {
                                $city_list = array_map('strtolower', include("cities/" . $store_id . ".php"));
                                foreach ($city_list as $key => $value) {

                                    if (strpos(strtolower($json['datapacket']['shipping_address']['address']), $value)) {
                                        $cities_data = [strtolower($value)];
                                        $amount = ['City' => strtolower($cities_data[0]), 'amount' => $total_amount];
                                        array_push($all_amounts, $amount);
                                        array_push($result, $cities_data[0]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $sum = array_reduce($all_amounts, function ($a, $b) {
                isset($a[$b['City']]) ? $a[$b['City']]['amount'] += $b['amount'] : $a[$b['City']] = $b;
                return $a;
            });
            $cities_count = array_count_values($result);
            $other_cities  = array_slice($final_total,  6);


            arsort($cities_count);
            foreach($cities_count as $word => $count)
            {
                $response=[
                    'city'=> $word,
                    'total_shipped'=>$count,
                    'total_order_value'=>$sum[$word]['amount']

                ];
                array_push($final_total,$response);
            }
            $top_five = array_slice($final_total, 0, 5, true);

            array_push($top_five_stats, $top_five);
            $other_cities = array_slice($final_total,  5);

            $total_shipped=0;
            $total_amount=0;
            $total_order_value=0;
            foreach($other_cities as $key=>$value){

               // $total_shipped =$total_shipped +$key;
                $total_shipped=$total_shipped+$value['total_shipped'];
                $total_order_value=$total_order_value+$value['total_order_value'];


            }

            $other_cities_stats=[
                'city'=>'others',
                'total_shipped'=>$total_shipped,
                'total_order_value'=>$total_order_value
            ];

            array_push($top_five_stats, $other_cities_stats);


            return $top_five_stats;


        }else{
            $null_result=array();

            $response = [
                'city' => "0",
                'total_shipped' => "0",
                'total_order_value' => "0"

            ];
            array_push($null_result, $response);
            $empty_array = [$null_result];
            $top_five= $empty_array;

            return $top_five;
        }
    }

    public function insertLogsData(){

        $data = Shipping::select('store_id','order_id','courier_name','courier_status','order_tracking_id')
            ->where('courier_name','=','leopard')
            ->where('courier_status','=','Delivered')
            ->where('id','>=',2000)
            ->where('id','<=',4500)
            ->get();

        foreach($data as $value) {

            $get_status_timeline = Helper::GetOrderStatusLeopard($value->order_tracking_id);

            if ($get_status_timeline!='Not Available') {
                $inserts = ['store_id' => $value->store_id, 'order_id' => $value->order_id,'courier_name'=>$value->courier_name, 'status_timeline' =>json_encode($get_status_timeline,true)];
                logs::create($inserts);



            }
        }

    }

    public function courierSettings($payload){

        $payld_json=$payload;
        $action = $payld_json['action'];
        // dd($payld_json);
        $requestController               = new requestValidationController();
        $checkValidation                 = $requestController->validateStore($payld_json);
        if($checkValidation['response']==true) {
            $store_id =$payld_json['dataPacket']['store_id'];
            $courier_name =$payld_json['dataPacket']['courier_name'];
            $credentals =json_encode($payld_json['dataPacket']['credentials'], true);
            $logix =json_encode($payld_json['dataPacket']['logix'],true);
            $status = $payld_json['dataPacket']['status'];
            $est_delivery  = json_encode($payld_json['dataPacket']['est_delivery'],true);
            $origin_city =$payld_json['dataPacket']['origin_city'];


            $internatinal ='0';
            $credentials =$payld_json['dataPacket']['credentials'];
            $check_if_shipper_exists = Settings::where('courier_name',$courier_name)->where('store_id',$store_id)->first();

            if(count($check_if_shipper_exists)==0) {

                $data = ['store_id' => $store_id, 'courier_name' => $courier_name, 'credentials' => $credentals, 'logix' => $logix,'status'=>$status,'origin_city'=>$origin_city, 'est_delivery'=>$est_delivery];

                Settings::create($data);
              $check_all_cities = $payld_json['dataPacket']['logix']['domestic']['include_city'][0];
                if(strcmp($check_all_cities,'All Cities')==0){
                    $cities["cities"] = array_map('strtolower', include("cities/".$courier_name.".php"));

                    $excluded_cities= $payld_json['dataPacket']['logix']['domestic']['exclude_city'];
                    if(!empty($excluded_cities)){
                        foreach($excluded_cities  as $value) {
                            if (($key = array_search($value, $cities['cities'])) !== false) {
                                unset($cities['cities'][$key]);
                                file_put_contents("cities/" . $store_id . ".json", json_encode($cities['cities'],true));

                            }
                        }
                    }
                }

                $response = array_merge(Helper::constantResponse($action, 200, 'Success'), ['dataPacket' => ['Success']]);
                return $response;
            }else{
                $response = array_merge(Helper::constantResponse($action, 500, 'Shipper already exists'), ['dataPacket' => ['Shipper already exists']]);
                return $response;
            }

        }else{
            $response = Helper::ifValidationFalse($action, $checkValidation);

            return $response;
        }


    }
    public function UpdateCourierStatus(){

        $filter = ['leopard'];
        $order_ids = Shipping::select('order_id', 'courier_name','store_id')->Where('courier_status','!=','Delivered')->whereIn('courier_name',$filter)->get();

        foreach($order_ids as $ids){

            $courier_name = $ids->courier_name;
            $order_id     = $ids->order_id;
            $store_id     = $ids->store_id;

            $cron_name = strtolower('cron'.$courier_name);
            $cronController= new ApiCronController();
            if(strtolower(method_exists($cronController,$cron_name))){
                $cronController->$cron_name($order_id,$store_id,$courier_name);

            }
        }
    }

    public function testFunction($payload){


        $result=Helper::woocommerceOrderNoteCurlRequest("http://develop.technifydev.com/vendor/wordpress/wordpress-v48/instance2","ok","388");
        dd($result);
    }

    public function magentoObjectTransfer($payload){
        $data = $payload;
        $username= $data['dataPacket']['auth']['username'];
        $password = $data['dataPacket']['auth']['pass'];
        $order_increment_id = $data['dataPacket']['increment_id'];
        $endpoint=$data['dataPacket']['endpoint'];
        $client = new SoapClient($endpoint);
        $session = $client->login($username, $password);
        $result = $client->call($session, 'sales_order.info', $order_increment_id);
        return $result;
    }

    public function magentoGetOrderStatus($payload) {
        
        $datapacket = $payload['dataPacket'];
        $endpoint = $datapacket['endpoint'];
        $username = $datapacket['auth']['username'];
        $pass = $datapacket['auth']['pass'];            
        
        $client = new SoapClient($endpoint);
        $session = $client->login($username, $pass);
        $param = ['sessionId' => $session, 'storeView' => 0];
        $order_status = $client->call($session, 'serviceorder.getOrderStatuses', $param);                      
        if( is_array($order_status) ) {
            return ['response' => true, 'orderStatus' => $order_status];
        } else{
            return ['response' => false];
        }
    }

    public function magentoUpdateOrderStatus($payload){
        
        $datapacket = $payload['dataPacket'];
        $endpoint = $datapacket['endpoint'];
        $username = $datapacket['auth']['username'];
        $pass = $datapacket['auth']['pass'];
        $param = $datapacket['payload'];
        
        $client = new SoapClient($endpoint);
        $session = $client->login($username, $pass);
        $result = $client->call($session, 'serviceorder.update', ['payload' => $param]);
        if( is_array($result) ) {
            return 'true';
        }else{
            return 'false';
        }
    }

     public function getOrderViaMobileOrderidTrackingid($payload)
     {
        $orderInfo=array();
         $requestController = new requestValidationController();
         $checkValidation = $requestController->validateStore($payload);
         $all_orders_pyld = $payload['dataPacket'];
         $action = $payload['action'];

         if ($checkValidation['response']) {

             $orderList = DB::table('shippings')->select('order_id','courier_status','courier_name', 'order_tracking_id','orderinfo','customer_number','created_at')
                 ->where(function ($query) use ($payload) {

                     $order_data = $payload;

                     if ($order_data['dataPacket']['order_id'] != '') {
                         $query->where('order_id', $order_data['dataPacket']['order_id'])->where('store_id', $order_data['dataPacket']['store_id']);;
                     }
                 })->where(function ($query) use ($payload) {

                     $order_data = $payload;

                     if ($order_data['dataPacket']['order_tracking_id'] != '') {
                         $query->where('order_tracking_id', $order_data['dataPacket']['order_tracking_id'])->where('store_id', $order_data['dataPacket']['store_id']);
                     }
                 })->where(function ($query) use ($payload) {

                     $order_data = $payload;

                         if ($order_data['dataPacket']['mobile_number'] != '') {
                             $query->where('customer_number', $order_data['dataPacket']['mobile_number'])->where('store_id', $order_data['dataPacket']['store_id']);
                         }

                 })->get();

              foreach($orderList as $key=>$value){
                  $json_decode = json_decode($value->orderinfo,true);
                  $total_amount = Helper::getOrderTotalAmount($json_decode['datapacket']['total']);

                  $response=[
                    'order_id'=>$value->order_id,
                    'courier_name'=> $value->courier_name,
                    'courier_status'=>$value->courier_status,
                    'order_amount'=> $total_amount,
                      'cutomer_number'=>$value->customer_number,
                    'tracking_id'=>$value->order_tracking_id,
                    'created_at'=>$value->created_at
                  ];
                  array_push($orderInfo, $response);


              }


             if (count($orderList) > 0) {
                 $store_name = Helper::GetStoreNameByIDOrEmail($payload['dataPacket']['store_id']);
                 $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store_name'=>$store_name,'orderInfo' => $orderInfo]]);
             } else {
                 $msg = "No data found";
                 $response = array_merge(Helper::constantResponse($action, 500, ''), ['dataPacket' => ['errorMessage' => $msg]]);
             }}else {
             $response = Helper::ifValidationFalse($action, $checkValidation);
         }
         return $response;


     }






    public function getAllOrders($payload){

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);
        $all_orders_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $store_id = $all_orders_pyld['store_id'];
        if($checkValidation['response']) {
            $orderList = DB::table('shippings')->select('order_id','courier_name','order_tracking_id','courier_status', 'orderinfo', 'status', 'created_at')->where('store_id',$store_id)
                ->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if($order_data['dataPacket']['start_date']  != '' ){
                        $query->where('created_at', '>=',$order_data['dataPacket']['start_date']);
                    }
                })->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if($order_data['dataPacket']['end_date']  != ''){
                        $query->where('created_at', '<=',$order_data['dataPacket']['end_date']);
                    }
                })->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if( $order_data['dataPacket']['status']  != ''){
                        $query->where('status', '=',$order_data['dataPacket']['status']);
                    }
                })->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if($order_data['dataPacket']['last_orders']  != ''){
                        $query->limit(2);
                    }
                });


            $orderList2 = DB::table('order_failure')->select('order_id', DB::raw("NULL as courier_name"),DB::raw("NULL as courier_status"),DB::raw("NULL as order_tracking_id"), 'orderinfo', 'status', 'created_at')->union($orderList)->where('store_id',$store_id) ->orderBy('order_id','desc')

                ->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if($order_data['dataPacket']['start_date']  != '' ){
                        $query->where('created_at', '>=',$order_data['dataPacket']['start_date']);
                    }
                })->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if($order_data['dataPacket']['end_date']  != ''){
                        $query->where('created_at', '<=',$order_data['dataPacket']['end_date']);
                    }
                })->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if( $order_data['dataPacket']['status']  != ''){
                        $query->where('status', '=',$order_data['dataPacket']['status']);
                    }
                })->where(function ($query) use ($payload) {

                    $order_data = $payload;

                    if ($order_data['dataPacket']['status'] != '') {
                        $query->where('status', '=', $order_data['dataPacket']['status']);
                    }

                })->get();

            $allOrders=array();


            $getTotalOrderAmount=0;
            foreach($orderList2 as $key=>$value){
             $order_amount = json_decode($value->orderinfo,true);

             if(isset($order_amount['action'])){

                 $total = $order_amount['datapacket']['total'];

                 $getTotalOrderAmount = apphelper::getOrderTotalAmount($total);


             }elseif(isset($order_amount['orderinfo'])){
                 $total = $order_amount['total'];
                 $getTotalOrderAmount = apphelper::getOrderTotalAmount($total);


             }

             $response=[
                 'order_id'=>$value->order_id,
                 'courier_name'=>$value->courier_name,
                 'order_tracking_id'=>$value->order_tracking_id,
                 'courier_status'=>$value->courier_status,
                 'total_amount'=>$getTotalOrderAmount,
                 'status'=>$value->status,
                 'created_at'=>$value->created_at
             ];

                array_push($allOrders,$response);
            }


            if (count($orderList2) > 0) {
                $store_name = Helper::GetStoreNameByIDOrEmail($store_id);
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store_name'=>$store_name,'allOrders' => $allOrders]]);
            } else {
                $msg = "No data found";
                $response = array_merge(Helper::constantResponse($action, 500, ''), ['dataPacket' => ['errorMessage' => $msg]]);
            }}else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;
    }






    public function getCourierStatus($payload){
        {
            $get_courier_status_pyld = $payload['dataPacket'];
            $action = $payload['action'];
            $requestController = new requestValidationController();
            $checkValidation = $requestController->validateStore($payload);
            if( $checkValidation['response'] ) {

                $filter = ['store_id' => $get_courier_status_pyld['store_id']];
                $orderList = Shipping::select('order_id','courier_status')->where($filter)->get();
                if (count($orderList) > 0) {
                    $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['courier_status' => $orderList]]);
                } else {
                    $msg = 'No data found';
                    $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                }
            } else {
                $response = Helper::ifValidationFalse($action, $checkValidation);
            }

            return $response;
        }
    }

       public function getCourierSettingsById($payload){
  
       $payld = $payload;
      $response_array = array();
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payld);
        $courier_settinfgs_pyld = $payld['dataPacket'];
        $action = $payld['action'];
        $store_id = $courier_settinfgs_pyld['store_id'];
        $filter = ['store_id' => $store_id];
         $courier_settings = Settings::select('store_id', 'courier_name', 'credentials','status','logix','est_delivery','origin_city')->distinct()->where($filter)->get();

        if( $checkValidation['response'] ) {
            if (count($courier_settings) > 0) {


             foreach ($courier_settings as $data) {
                    $response=[

                        'courier_name'=>$data->courier_name,
                        'status'      =>$data->status,
                        'origin_city'=>$data->origin_city,
                        'logix'       =>json_decode($data->logix),
                        'est_delivery'=>json_decode($data->est_delivery)
                        ];
                    array_push($response_array, $response);
                    
                        
                }
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $response_array]);

            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }

        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }
    

     public function getCourierSettingsByCourierName($payload){
        $payld = $payload;
        $response_array = array();
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payld);
        $courier_settinfgs_pyld = $payld['dataPacket'];
        $action = $payld['action'];
        $store_id = $courier_settinfgs_pyld['store_id'];
        $courier_name = $courier_settinfgs_pyld['courier_name'];
        $filter = ['store_id' => $store_id, 'courier_name'=>$courier_name];
        $courier_settings = Settings::select('store_id', 'courier_name', 'credentials','status','logix','est_delivery','origin_city')->distinct()->where($filter)->get();

        if( $checkValidation['response'] ) {
            if (count($courier_settings) > 0) {
                foreach ($courier_settings as $data) {
                    $response=[
                         
                        'courier_name'=>$data->courier_name,
                        'credentials' =>json_decode($data->credentials),
                        'status'      =>$data->status,
                        'origin_city'=>$data->origin_city,
                        'logix'       =>json_decode($data->logix),
                        'est_delivery'=>json_decode($data->est_delivery)
                        
                    ];
                    array_push($response_array, $response);
                }
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $response_array]);

            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }

        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;

    }

    public function getCourierDetails($payload){

        $courier_details          = array();
        $get_courier_details_pyld = $payload['dataPacket'];
        $order_ids                = $get_courier_details_pyld['order_id'];
        $action                   = $payload['action'];
        $requestController        = new requestValidationController();
        $checkValidation          = $requestController->validateStoreAndOrderID($payload);

        if( $checkValidation['response'] ) {

            $filter = ['store_id' => $get_courier_details_pyld['store_id'], 'order_id' => $get_courier_details_pyld['order_id']];
            foreach($order_ids as $id ) {

                $orderList = Shipping::select('order_id', 'order_tracking_id', 'courier_status','orderinfo', 'courier_name','created_at')->where('store_id', $get_courier_details_pyld['store_id'])->where('order_id', $id['order_id'])->first();
               
                 $orderList2 = Settings::select('est_delivery')->where('store_id', $get_courier_details_pyld['store_id'])->first();


                if (count($orderList) > 0 && count($orderList2)>0) {

                    $order_status_id  = json_decode($orderList->orderinfo,true);
                    $response = [
                        $orderList->order_id => [
                            'order_status_id'=>end($order_status_id['datapacket']['order_history'])['order_status_id'],
                            'order_tracking_id'      => $orderList->order_tracking_id,
                            'courier_status'         => $orderList->courier_status,
                            'courier_name'           => $orderList->courier_name,
                            'order_booking_time'     =>$orderList->created_at->day.'-'.$orderList->created_at->month.'-'.$orderList->created_at->year,
                            'est_delivery'=>json_decode($orderList2->est_delivery)
                        ],
                    ];
                    array_push($courier_details, $response);
                } else {
                    $null_response = [
                        $id['order_id'] =>"No data found"
                    ];
                    array_push($courier_details, $null_response);
                }
            }
            if (count($orderList) > 0) {
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['courierDetails' => $courier_details]]);
            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 200, $msg), ['dataPacket' => ['courierDetails' => $courier_details]]);
            }
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

    public function updateCourierDetails($payload){
        $update_courier_details_pyld = $payload['dataPacket'];
        $action = $payload['action'];
        $order_id = $update_courier_details_pyld['order_id'];
        $store_id = $update_courier_details_pyld['store_id'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);
        if( $checkValidation['response'] ) {

            $filter = ['store_id' => $update_courier_details_pyld['store_id'], 'order_id' => $update_courier_details_pyld['order_id']];
            $orderList = Shipping::select('courier_name')->where($filter)->first();
            $cron_name = strtolower('cron'.$orderList->courier_name);
            $cronController= new ApiCourierStatusController();
            if(strtolower(method_exists($cronController,$cron_name))){
               $order_status= $cronController->$cron_name($order_id,$store_id,$orderList->courier_name);
            }
            $courier_details = Shipping::select('order_tracking_id','courier_status','courier_name')->where($filter)->first();
             if(count($courier_details)>0) {
                 $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['courierDetails' => $courier_details]]);

             }else{
                 $msg = 'No data found';
                 $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
             }
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }

  public function updateCourierSettings($payload){
        $payld             = $payload;
        $response_array    = array();
        $requestController = new requestValidationController();
        $checkValidation   = $requestController->validateStore($payld);
        $update_settings_pyld = $payld['dataPacket'];
        $action = $payld['action'];
        $store_id = $update_settings_pyld['store_id'];
        $courier_name = $update_settings_pyld['courier_name'];
        $credentials = $update_settings_pyld['credentials'];
        $est_delivery=$update_settings_pyld['est_delivery'];
        $origin_city = $update_settings_pyld['origin_city'];
        if( $checkValidation['response'] ) {
        $update_query = Settings::where('store_id', $update_settings_pyld['store_id'])->where('courier_name', $update_settings_pyld['courier_name'])
            ->update([
                'credentials'  =>json_encode($update_settings_pyld['credentials'],true),
                'status'       => $update_settings_pyld['status'],
                'origin_city'  =>$update_settings_pyld['origin_city'],
                'logix'        => json_encode($update_settings_pyld['logix'], true),
                'est_delivery'=>json_encode($update_settings_pyld['est_delivery'],true)

            ]);


            if ($update_query == false) {
                $msg = 'No data found or nothing to update';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            } elseif ($update_query == true) {
                $msg = 'Success';
                $response = array_merge(Helper::constantResponse($action, 200, $msg), ['dataPacket' => ['status' => $msg]]);
            } else {
                $msg = 'No data found';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }
        }
        else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }
    public function getTotalShipmentValue($orders){
        
        $total = 0;
        foreach( $orders as $data ){
            $orderinfo = json_decode($data->orderinfo, true);
            $order_amount = $orderinfo['datapacket']['total'];
            $total += Helper::getOrderTotalAmount($order_amount);
        }
        
        return $total;
    }

    // get store specification
    public function getStoreSpec($store_id) {

        $url = Config::get('urls.spec_urls.store_spec').$store_id.'.json';


        $store_spec = file_get_contents($url);

        return $store_spec;
    }
}