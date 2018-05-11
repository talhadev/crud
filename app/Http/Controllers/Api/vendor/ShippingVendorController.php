<?php

namespace App\Http\Controllers\Api\vendor;
use App\helpers\courier_tracker;
use App\helpers\curl_request;
use App\helpers\helpers;
use App\Models\Orderfailure;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Config;
use PDF;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Api\Models\Orderproduct;
use \Cviebrock\EloquentSluggable\Services\SlugService;
use App\Models\Stores;
use Illuminate\Support\Str;
use Input;

use App\Http\Controllers\Api\vendor\ResponseController;
use App\Models\Shipping;
use phpDocumentor\Reflection\Types\Null_;
use Response;
use DB;


class ShippingVendorController extends Controller
{

/*  public function __construct(Request $request)
    {
        $payload_json = json_decode($request->payload, true);
        $params = ['payload' => '{"action":"checkAuth","dataPacket":{"token":""},  "time_stamp": "2017-06-16 04:33:58"}'];
        $headers = ['Authorization' => $request->header('Authorization'), 'Accept' => $request->header('Accept')];
        $url = Config::get('urls.navigation_urls.brain_server', true);
        $headers = helpers::get_header_array($headers);
       $response      = curlRequest($url, $headers, $params);
       $authorization = json_decode($response, true);
       if ($authorization['action'] == 'Authorization') {
           $response  = generateResponse($payload_json['action'], 500, false, 'Invalid token');
           dd($response);
      }

    }*/

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
    {   dd($payload);
        $response_data = array();
        $payload_json = json_decode($request->payload, true);
        $validator = Validator::make(
            array(
                'store_id' => $payload_json['dataPacket']['store_id']),
            array(
                'store_id' => 'required|integer',)
        );

        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response = helpers::generateResponse($payload_json ['action'], 500, false, $error_messages);
            return $response;
        } else {
            $orderList = Shipping::where('status', '=', '1')->where('store_id', $payload_json['dataPacket']['store_id'])->get();

            if ($orderList == true) {
                if (count($orderList) > 0) {
                    foreach ($orderList as $data) {
                        $orderinfo = json_decode($data->orderinfo, true);
                        $order_info = $orderinfo['datapacket'];
                        foreach ($order_info as $info) {
                            $response = [
                                'store_id' => $data->store_id,
                                'store_name' => $order_info['orderinfo']['store_name'],
                                'order_id' => $order_info['orderinfo']['order_id'],
                                'courier_company' => $data->courier_name,
                                'order_tracking_id' => $data->order_tracking_id,
                                'status' => 'Success',
                                'created_at' => $data->created_at
                            ];
                            array_push($response_data, $response);
                            break;
                        }
                    }
                    $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);
                    return $response;
                }
                $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                return $response;
            }
        }
    }

    public function failedOrders(Request $request)
    {

        $response_data = array();
        $payload_json = json_decode($request->payload, true);
        $validator = Validator::make(
            array(
                'store_id' => $payload_json['dataPacket']['store_id']),
            array(
                'store_id' => 'required|integer',)
        );
        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response = helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
            return $response;
        } else {

            $store_id = $payload_json['dataPacket']['store_id'];
            $orderList = Orderfailure::where('store_id', $store_id)->get();

            if ($orderList == true) {
                if (count($orderList) > 0) {

                    $name = Stores::select('name')->where('id', $payload_json['dataPacket']['store_id'])->first();
                    foreach ($orderList as $data) {

                        $response = [
                            'store_id' => $data->store_id,
                            'store_name' => $name->name,
                            'order_id' => $data->order_id,
                            'city' => $data->failure_city,
                            'address' => $data->failure_address,
                            'phone' => $data->telephone,
                            'status' => 'Failure',
                            'created_at' => $data->created_at];
                        array_push($response_data, $response);
                    }

                    $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);
                    return $response;

                } else {
                    $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                    return $response;


                }
            }
        }
    }

    public function cancelledOrders(Request $request)
    {

        $response_data = array();
        $payload_json = json_decode($request->payload, true);
         
        $validator = Validator::make(
            array(
                'store_id' => $payload_json['dataPacket']['store_id']),
            array(
                'store_id' => 'required|integer')
        );
        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response = generateResponse($payload_json['action'], 500, false, $error_messages);
            return $response;
        } else {
            $store_id = $payload_json['dataPacket']['store_id'];
            $orderList = Shipping::where('status', '=', '2')->where('store_id', $store_id)->get();
            if ($orderList == true) {

                if (count($orderList) > 0) {
                    foreach ($orderList as $data) {
                        $orderinfo = json_decode($data->orderinfo, true);
                        $order_info = $orderinfo['datapacket'];
                        foreach ($order_info as $info) {
                            $response = [
                                'store_id' => $data->store_id,
                                'store_name' => $order_info['orderinfo']['store_name'],
                                'order_id' => $order_info['orderinfo']['order_id'],
                                'courier_company' => $data->courier_name,
                                'order_tracking_id' => $data->order_tracking_id,
                                'status' => 'cancelled',
                                'created_at' => $data->created_at];
                            array_push($response_data, $response);
                            break;
                        }
                    }
                    $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);
                    return $response;
                } else {
                    $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                    return $response;
                }
            }
        }
    }

    public function updateOrder(Request $request)
    {

        $payload_json = json_decode($request->payload, true);
        $validator = Validator::make(
            array(
                'store_id' => $payload_json['dataPacket']['store_id'],
                'order_id' => $payload_json['dataPacket']['order_id'],
                'address' => $payload_json['dataPacket']['address'],
                'city' => $payload_json['dataPacket']['city'],
                'phone' => $payload_json['dataPacket']['phone'],),
            array(
                'store_id' => 'required|integer',
                'order_id' => 'required|integer',)
        );
        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response = helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
            return $response;
        } else {
            $orderList = Orderfailure::where('store_id', $payload_json['dataPacket']['store_id'])->where('order_id', $payload_json['dataPacket']['order_id'])
                ->update([
                    'failure_address' => $payload_json['dataPacket']['address'],
                    'failure_city' => $payload_json['dataPacket']['city'],
                    'telephone' => $payload_json['dataPacket']['phone'],
                ]);

            if ($orderList == false) {
                $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found or nothing to update');
                return $response;
            } elseif ($orderList == true) {
                $response = helpers::generateResponse($payload_json['action'], 200, true, 'Success');
                return $response;

            } else {
                $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                return $response;
            }


        }

    }

    public function getFailedOrderDetail(Request $request)
    {
        $response_data = array();
        $payload_json = json_decode($request->payload, true);
        $validator = Validator::make(
            array(
                'store_id' => $payload_json['dataPacket']['store_id'],
                'order_id' => $payload_json['dataPacket']['order_id']),
            array(
                'store_id' => 'required|integer',
                'order_id' => 'required|integer')
        );
        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response =helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
            return $response;
        } else {
            $orderList = Orderfailure::where('store_id', $payload_json['dataPacket']['store_id'])->where('order_id', $payload_json['dataPacket']['order_id'])->get();

            if ($orderList == true) {
                if (count($orderList) > 0) {

                    $name = Stores::select('name')->where('id', $payload_json['dataPacket']['store_id'])->first();
                    foreach ($orderList as $data) {
                        $response = [
                            'store_id' => $data->store_id,
                            'store_name' => $name->name,
                            'order_id' => $data->order_id,
                            'city' => $data->failure_city,
                            'address' => $data->failure_address,
                            'phone' => $data->telephone,
                            'status' => $data->status,
                            'created_at' => $data->created_at];
                        array_push($response_data, $response);
                    }

                    $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);
                    return $response;

                } else {
                    $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                    return $response;


                }
            }
        }
    }

    public function updateAndproceed(Request $request)
    {
        $payload_json = json_decode($request->payload, true);
        $validate = validateUpdateOrders($payload_json['action'], $payload_json['dataPacket']['store_id'], $payload_json['dataPacket']['order_id'], $payload_json['dataPacket']['city'], $payload_json['dataPacket']['address'], $payload_json['dataPacket']['phone']);
        if ($validate->fails()) {
            $error_messages = $validate->messages()->all();
            $response = helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
            return $response;
        } else {
            $check_status = orderStatus($payload_json['dataPacket']['store_id'], $payload_json['dataPacket']['order_id']);

            if ($check_status['status'] == '0') {
                $orderList = Orderfailure::where('store_id', $payload_json['dataPacket']['store_id'])->where('order_id', $payload_json['dataPacket']['order_id'])
                    ->update([
                        'failure_address' => $payload_json['dataPacket']['address'],
                        'failure_city' => $payload_json['dataPacket']['city'],
                        'telephone' => $payload_json['dataPacket']['phone'],
                    ]);

                if ($orderList == true) {

                    if ($payload_json['dataPacket']['order_id']) {
                        $failure_order_ids = [$payload_json['dataPacket']['order_id']];

                    } else {
                        $failure_order_ids = array_slice(explode(',', implode(',', $request->all())), 1);
                    }
                    $i = 0;
                    foreach ($failure_order_ids as $key => $order_id) {
                        print_r($i);
                        $get_failure_order = Orderfailure::where('order_id', $order_id)->first();
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
                        $response = curl_request::curlRequestUpdateAndProceed(1, $url, $useragent, $json->payload);
                        $i++;
                        if ($response['response'] == true) {

                            $update_faliure_order = Orderfailure::where('order_id', $payload_json['dataPacket']['order_id']);

                            $data = ['status' => '1', 'orderinfo' => json_encode($json->payload)];
                            $update_faliure_order->update($data);
                            $result = helpers::generateResponse($payload_json['action'], 200, true, 'Order Update and Proceeded');
                            return $result;


                        }


                    }
                    $result = helpers::generateResponse($payload_json['action'], 500, false, 'Could not update and proceed');
                    return $result;
                }

            } else {
                $result =helpers::generateResponse($payload_json['action'], 500, false, 'Order already proceeded');
                return $result;
            }
        }

    }

    public function proceed(Request $request)
    {
        $payload_json = json_decode($request->payload, true);
        // $validate = validateUpdateOrders($payload_json['action'], $payload_json['dataPacket']['store_id'], $payload_json['dataPacket']['order_id']);
        $validator = Validator::make(
            array(
                'store_id' => $payload_json['dataPacket']['store_id'],
                'order_id' => $payload_json['dataPacket']['order_id']),
            array(
                'store_id' => 'required|integer',
                'order_id' => 'required|array|',
                'order_id.*' => 'integer')
        );
        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response = helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
            return $response;
        } else {
            // $check_status = orderStatusForMultipleIds($payload_json['dataPacket']['store_id'], $payload_json['dataPacket']['order_id']);

            if ($payload_json['dataPacket']['order_id']) {
                $failure_order_ids = $payload_json['dataPacket']['order_id'];


            } else {
                $failure_order_ids = array_slice(explode(',', implode(',', $request->all())), 1);
            }

            $success_responses = 0;
            foreach ($failure_order_ids as $order_id) {


                $get_failure_order = Orderfailure::where('order_id', $order_id)->first();

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
                $response =curl_request::curlRequestUpdateAndProceed(1, $url, $useragent, $json->payload);

                if ($response['response'] == true) {
                    $success_responses++;
                }

            }
            if ($success_responses == count($failure_order_ids)) {
                $update_faliure_order = Orderfailure::whereIn('order_id', $failure_order_ids);
                $data = ['status' => '1', 'orderinfo' => json_encode($json->payload)];
                $update_faliure_order->update($data);
                $result = helpers::generateResponse($payload_json['action'], 200, true, 'All orders Proceeded');
                return $result;
            } else {
                $result = helpers::generateResponse($payload_json['action'], 500, false, 'Could not proceed all orders');
                return $result;
            }

        }
    }

    public function airwayBill(Request $request){

            $payload_json = json_decode($request->payload, true);
            $validator = helpers::validateStoreIdAndOrderIds($payload_json['dataPacket']['store_id'], $payload_json['dataPacket']['order_id']);

            if ($validator->fails()) {

                $error_messages = $validator->messages()->all();
                $response = helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
                return $response;
            }

            else {
              $response_data=array();
              $courier_data = Shipping::where('store_id', $payload_json['dataPacket']['store_id'])->whereIn('order_id', $payload_json['dataPacket']['order_id'])->get();


              if (count($courier_data) > 0) {

                  foreach ($courier_data as $data) {
                    if ($data->courier_name == Config::get('courier_names.couriers.TCS')) {
                        $view_data = courier_tracker::GetCNDetailsByReferenceNumberTCS($data->order_id);

                        $response = helpers::generateResponse($payload_json['action'], 200, true, $view_data);

                    } elseif ($data->courier_name == Config::get('courier_names.couriers.Kangaroo')) {
                        $view_data = courier_tracker::GetCNDetailsByOrderIdKangroo($data->order_id);
                        $view = \View::make('shipping.airwayBills.kangaroo')->with($view_data);
                        return $view;
                    } elseif ($data->courier_name == Config::get('courier_names.couriers.Leopard')) {
                        $data = courier_tracker::GetCNDetailsByReferenceNumberLeopard($data->order_tracking_id);

                        $view = \View::make('shipping.airwayBills.leopard')->with($data);
                   // PDF::loadHTML($view)->setPaper('a4')->setOrientation('landscape')->setOption('margin-bottom', 0)->save('4.pdf');
                        return $view;
                  //  $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);


                    }elseif ($data->courier_name == Config::get('courier_names.couriers.BlueEx')) {
                      $data =courier_tracker::GetCNDetailsByReferenceNumberBlueEx();

                      $view = \View::make('blueex.airwayBill');
                      return $view;

                  //$response = helpers::generateResponse($payload_json['action'], 200, true, $$response_data);


                  }

                  else {
                    $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');


                }
            }
            return $response;
        }
    }
    }

    public function logicsOrder(Request $request){
        $response_data=array();

        $payload_json = json_decode($request->payload, true);
        $validator = Validator::make(
            array(
                'courier_name' => $payload_json['dataPacket']['courier_name'],
                ),
            array(
                'courier_name' => 'required',)
            );
        if ($validator->fails()) {
            $error_messages = $validator->messages()->all();
            $response = helpers::generateResponse($payload_json ['action'], 500, false, $error_messages);
            return $response;
        }
        else{


            $orderList = DB::table('shippings')->where('courier_name',$payload_json['dataPacket']['courier_name'])
            ->where(function ($query) {
                global $request;
                $payload=$request->payload;
                $order_data = json_decode($payload, true);
                if ($order_data['dataPacket']['store_id'] != '') {
                    $query->where('store_id',$order_data['dataPacket']['store_id']);

                }

            })->where(function ($query) {
                global $request;
                $payload=$request->payload;
                $order_data = json_decode($payload, true);
                if ($order_data['dataPacket']['month'] != '') {
                    $query->whereMonth('created_at',$order_data['dataPacket']['month']);

                }

            })->get();
            if (count($orderList) > 0) {



                foreach ($orderList as $data) {
                    $name = Stores::select('name')->where('id', $data->store_id)->first();
                    $json= json_decode($data->orderinfo, true);

                    $response = [
                    'store_name' => $name->name,
                    'order_id' => $data->order_id,
                    'courier_name'=>$data->courier_name,
                    'total'=>(end($json['datapacket']['total'])['title'] == 'Total') ? end($json['datapacket']['total'])['value'] : null,
                    'commision'=>null,
                    'status' => $data->status,
                    'created_at' => $data->created_at];
                    array_push($response_data, $response);
                }
                $response = helpers::generateResponse($payload_json['action'], 200, true, $response_data);
                return $response;

            } else {
                $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                return $response;


            }

        }
    }

    public function shippingStats(Request $request){
            set_time_limit(200);
            $getTopFiveAllTimeCities         = $this->getCitiesTopFiveAllTime();
            $getCitiesTopFiveMonthly         = $this->getCitiesTopFiveMonthly();
            $response_data  = array();
            $response_data2 = array();
            $payload_json   = json_decode($request->payload, true);
            $validator      = Validator::make(
                array(

                    'store_id' => $payload_json['dataPacket']['store_id']),
                array(
                    'store_id' => 'required|integer',

                    ));
            if ($validator->fails()) {
                $error_messages = $validator->messages()->all();
                $response       = helpers::generateResponse($payload_json['action'], 500, false, $error_messages);
                return $response;
            } else {
                $month_data           = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('status',1)->get();
                $all_data             = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->where('status',1)->get();
                $totalTcsOrders1      = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name','tcs')->count();
                $totalKangarooOrders1 = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name','kangaroo')->count();
                $totalLeopardOrders1  = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->where('courier_name','leopard')->count();
                $totalShippedOrders1  = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->whereRaw('MONTH(created_at) = MONTH(NOW())')->count();
                $totalShippedOrders   = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->count();
                $totalTcsOrders       = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->where('courier_name','tcs')->count();
                $totalKangarooOrders  = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->where('courier_name','kangaroo')->count();
                $totalLeopardOrders   = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->where('courier_name','leopard')->count();
                $totalShippedOrders   = Shipping::where('store_id',$payload_json['dataPacket']['store_id'])->count();

                if(count($month_data)>0){
                    $current_month=[
                            'perod'               =>'currentMonth',
                            'total_shipped_orders'=>$totalShippedOrders1,
                            'total_value'         =>$this->getTotalShipmentValue($month_data),
                            'total_orders'=>[
                                'tcs'     => $totalTcsOrders1,
                                'Kangaroo'=>$totalKangarooOrders1,
                                'Leapord' =>$totalLeopardOrders1
                            ],
                        'citiesStats'=>[
                            $getCitiesTopFiveMonthly
                        ]
                            ];
                            $allData=[
                            'perod'               =>'allData',
                            'total_shipped_orders'=>$totalShippedOrders,
                            'total_value'         =>$this->getTotalShipmentValue($all_data),
                            'total_orders'        =>[
                                'tcs'     => $totalTcsOrders,
                                'Kangaroo'=>$totalKangarooOrders,
                                'Leapord' =>$totalLeopardOrders,
                            ],
                            'citiesStats'=>[
                                $getTopFiveAllTimeCities
                            ]
                        ];
                    array_push($response_data,$current_month);
                    array_push($response_data2,$allData);
                    $response = helpers::generateResponse2($payload_json['action'], 200, true, $response_data,$response_data2);
                    return $response;
                }
                else{

                    $response = helpers::generateResponse($payload_json['action'], 500, false, 'No data found');
                    return $response;
                }
            }
    }
          
    public function getCitiesTopFiveAllTime(){

            $result = array();
            $final_total=array();
            $all_amounts=array();
            $json = json_decode($this->getStoreSpec("100001"), true);
            $orderinfo = Shipping::select('orderinfo')->get();

            foreach($orderinfo as $data) {

                   $json = json_decode($data->orderinfo, true);
                   $total_amount=(end($json['datapacket']['total'])['title'] == 'Total') ? end($json['datapacket']['total'])['value'] : null;
                   $order_id = $json['datapacket']['orderinfo']['order_id'];

                   if (isset($json['ifCityIsNull'])) {
                       $cities_data = [strtolower($json['ifCityIsNull'])
                       ];
                       $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                       array_push($all_amounts, $amount);
                       array_push($result, $cities_data[0]);


                   }elseif ($json['datapacket']['shipping_address']['city'] != "null") {
                       $cities_data = [strtolower($json['datapacket']['shipping_address']['city'])
                       ];
                       $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                       array_push($all_amounts, $amount);
                       array_push($result, $cities_data[0]);

                   }
                   elseif ($json['datapacket']['shipping_address']['address'] != "null") {
                       $get_store_spec = json_decode($this->getStoreSpec("100001"), true);

                       if(isset($get_store_spec['shippingdetails'])) {
                           $cities = $get_store_spec['shippingdetails']['variables']['location'];

                           foreach ($cities as $key => $value) {
                               if(strpos(strtolower($json['datapacket']['shipping_address']['address']),$value['cities'][0])==true) {
                                   $cities_data = [strtolower($value['cities'][0])];
                                   $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                                   array_push($all_amounts, $amount);
                                   array_push($result, $cities_data[0]);




                               }elseif($value['cities'][0]=='else'){
                                   $city_list = array_map('strtolower', include("cities/"."100001.php"));
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

              arsort($cities_count);

            foreach($cities_count as $word => $count)
            {
                $response=[
                    'City'=> $word,
                    'total_shipped'=>$count,
                    'total_order_value'=>$sum[$word]['amount']

                ];
                array_push($final_total,$response);
                $top_five = array_slice($final_total, 0, 5, true);
            }


            return $top_five;


    }
    public function getCitiesTopFiveMonthly(){

        $result = array();
        $final_total=array();
        $all_amounts=array();
        $json = json_decode($this->getStoreSpec("100001"), true);
        $orderinfo = Shipping::select('orderinfo')->whereRaw('MONTH(created_at) = MONTH(NOW())')->get();

        foreach($orderinfo as $data) {

            $json = json_decode($data->orderinfo, true);
            $total_amount=(end($json['datapacket']['total'])['title'] == 'Total') ? end($json['datapacket']['total'])['value'] : null;
            $order_id = $json['datapacket']['orderinfo']['order_id'];

            if (isset($json['ifCityIsNull'])) {
                $cities_data = [strtolower($json['ifCityIsNull'])
                ];
                $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                array_push($all_amounts, $amount);
                array_push($result, $cities_data[0]);


            }elseif ($json['datapacket']['shipping_address']['city'] != "null") {
                $cities_data = [strtolower($json['datapacket']['shipping_address']['city'])
                ];
                $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                array_push($all_amounts, $amount);
                array_push($result, $cities_data[0]);

            }
            elseif ($json['datapacket']['shipping_address']['address'] != "null") {
                $get_store_spec = json_decode($this->getStoreSpec("100001"), true);

                if(isset($get_store_spec['shippingdetails'])) {
                    $cities = $get_store_spec['shippingdetails']['variables']['location'];

                    foreach ($cities as $key => $value) {
                        if(strpos(strtolower($json['datapacket']['shipping_address']['address']),$value['cities'][0])==true) {
                            $cities_data = [strtolower($value['cities'][0])];
                            $amount = ['City'=>strtolower($cities_data[0]),'amount'=>$total_amount];
                            array_push($all_amounts, $amount);
                            array_push($result, $cities_data[0]);




                        }elseif($value['cities'][0]=='else'){
                            $city_list = array_map('strtolower', include("cities/"."100001.php"));
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

        arsort($cities_count);

        foreach($cities_count as $word => $count)
        {
            $response=[
                'City'=> $word,
                'total_shipped'=>$count,
                'total_order_value'=>$sum[$word]['amount']

            ];
            array_push($final_total,$response);
            $top_five = array_slice($final_total, 0, 5, true);
        }


        return $top_five;


    }

    public function getTotalShipmentValue($ordersData){
        $total=0;


      //  dd(end($ordersData['datapacket']['total'])['title'] == 'Total') ? end($ordersData['datapacket']['total'])['value'] : null)
        foreach($ordersData as $data){
            $json=json_decode($data->orderinfo, true);

            $value=(end($json['datapacket']['total'])['title'] == 'Total') ? end($json['datapacket']['total'])['value'] : null;
            $total = $total + $value;

        }

        return $total;
    }
        // get store specification
    public function getStoreSpec($store_id) {

        $url = Config::get('urls.spec_urls.store_spec').$store_id.'sample.json';


        $store_spec = file_get_contents($url);

        return $store_spec;
    }
}




