<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;
use App;
use SoapClient;
use nusoap_client;

use App\Jobs\SendOrderSuccess;
use App\Jobs\SendOrderFailure;

//MODELS
use App\Models\Shipping;
use App\Models\Stores;
use App\Models\OrderStatusLog;

class ShippingController extends Controller
{
	protected $license_key, $namespace = 'App\\Http\\Controllers\Connector\\';
    public function index($payload)
    {
        ini_set('max_execution_time', 300); // 60 * 5 (5mins)
        $action = $payload['action'];
        $checkValidation = app('App\Http\Controllers\requestValidationController')->shipOrder($payload);        

        if( $checkValidation['response'] ) {

            $this->license_key = $payload['license_key'];
            $order_id = $payload['dataPacket']['order_id'];

            $order_status_id = $payload['dataPacket']['order_status_id'];
//            $order_status = isset($payload['dataPacket']['order_status']) ? $payload['dataPacket']['order_status'] : '';
            $store_info = Helper::getStoreInfo($this->license_key)['storeInfo'];
            $store_id = $store_info->technify_store_id;
            $module_actv = $store_info->module_active;

            if ($module_actv == '1') {
                $filter = compact('store_id', 'order_id');
                $order = Shipping::where($filter)->first();
                $check_status_is_ship = Helper::getShippingSettingsOrderStatus($store_id, 'ship'); //Line 1116
                $check_status_is_cancel = Helper::getShippingSettingsOrderStatus($store_id, 'cancel');

                if ($check_status_is_ship['response'] && $order && $check_status_is_ship['orderStatusID'] == $order_status_id) {
                    if ($order->status == '1' && $order->order_tracking_id) {
                        $tracking_id = $order->order_tracking_id;
//                        dd($tracking_id);
                        $packet = ['successMessage' => 'Order already been shipped', 'tracking_id' => $tracking_id];
                        $response = Helper::ifApiSuccess($action, $packet);

                    } else {
                        $city = $order->city;
                        $address = $order->address;
                        $support_email = Stores::select('support_email')->where('technify_store_id', $store_id)->first();
                        $data = Helper::getShippingcityWithCredentialsDb($store_id, $address, $city);
                        if ($data['response']) {
                            $courier_company = $data['courier_company'];
                            $order['courier_city'] = $data['city'];
                            $order['credentials'] = $data['credentials'];
                            $order['origincity'] = $data['origincity'];
                            $order['shipperInfo'] = $data['shipperInfo'];

                            $courier_resp = $this->{$courier_company}($order);

                            if ($courier_resp['response']) {

                                $tracking_id = $courier_resp['tracking_id'];
                                $order_id = $courier_resp['order_id'];
                                $courier_company = $courier_resp['courier_company'];

                                $courier_url = $courier_resp['courier_url'];

                                $data = ['order_id' => $order_id, 'tracking_id' => $tracking_id, 'courier_company' => $courier_company, 'courier_url' => $courier_url];

                                if ($support_email) {
                                    $emails = str_replace(" ", "", explode(',', $support_email->support_email));   // send emails for order success
                                    foreach ($emails as $email) {
                                        dispatch(new SendOrderSuccess($data, $email));
                                    }
                                }

                                /*$sms_payload = ['store_id' => $store_id, 'order_id' => $order_id, 'tracking_id' => $tracking_id, 'courier_company' => $courier_company, 'telephone' => $telephone, 'store_name' => $store_info->name];
                                $sms = Helper::sendSmsOnOrderShipped($sms_payload);*/

                                $packet = ['successMessage' => 'order successfully shipped to ' . $courier_company . ' via technify with tracking id ' . $tracking_id, 'courier_company' => $courier_company, 'order_id' => $order_id, 'tracking_id' => $tracking_id, 'order_status' => $check_status_is_ship['orderStatusID'], 'tracking_url' => $courier_url, 'store_id' => $store_id];

                                // add comment to vendor 
                                $platform = $store_info->platforms;
                                $auth = $store_info->auth;
                                $endpoint = $store_info->endpoint;

                                $controller = ucfirst($platform) . 'Controller';
                                $controller_name = App::make($this->namespace . $controller);
                                $result = (new $controller_name)->updateOrderStatus($endpoint, $auth, $packet);

                                unset($packet['store_id']);
                                $response = Helper::ifApiSuccess($action, $packet);
                            } else {
                                $response = Helper::ifValidationFalse($action, $courier_resp);
                            }

                        } else {
                            $data = ['order_id' => $order_id, 'app_url' => Config::get('urls.navigation_urls.technify_dashboard')];
                            if ($support_email) {
                                $emails = str_replace(" ", "", explode(',', $support_email->support_email));   // send emails for order success
                                foreach ($emails as $email) {
                                    dispatch(new SendOrderFailure($data, $email));
                                }
                            }
                            $packet = ['errorMessage' => 'something wrong in your order, please check email or visit your technify dashboard to complete your order'];
                            $response = Helper::ifValidationFalse($action, $packet);
                        }
                    }
                }
                elseif ($check_status_is_cancel['response'] && $order && $check_status_is_cancel['orderStatusID'] == $order_status_id) {
                    $this->cancelShipmentTcs($order);
                    if ($order->status == '1' && $order->order_tracking_id) {
                        if ($order) {
                            $courier_company = $order->courier_name;
                            $json = Helper::getStoreSpecDb($store_id);
                            foreach ($json as $settings) {
                                $location = json_decode($settings->locations, true);
                            }
                            $companies = isset($location['shippingdetails']['variables']['credentials']) ? $location['shippingdetails']['variables']['credentials'] : [];
                            $credentials = '';

                            foreach ($companies as $key => $value) {
                                if ($courier_company == $key) {
                                    $credentials = $value;
                                    break;
                                }
                            }


                            $cncl_shpmnt_pyld = ['order_id' => $order_id, 'store_id' => $store_id, 'credentials' => $credentials];
                            $method = 'cancelShipment' . ucfirst($courier_company);

                             if (method_exists($this, $method)) {
                                $cncl_resp = $this->$method($cncl_shpmnt_pyld);
                                if ($cncl_resp['response']) {
                                    $msg = $cncl_resp['successMessage'];
                                    $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['successMessage' => $msg]]);
                                } else {
                                    $msg = $cncl_resp['errorMessage'];
                                    $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                                }
                            } else {
                                $msg = 'Invalid method';
                                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                            }
                        }




                    }
                    else{
                        $packet = ['errormsg' => ['order status' => $order['status'] , '1 -> shipped' , '2 -> already cancelled' ]];
                        $response = Helper::ifApiSuccess($action, [$packet]);
                    }
                }
                else {
                    $log_data = compact('store_id','order_id', 'order_status_id');
                    OrderStatusLog::create($log_data);
                    $packet = ['successMessage' => 'log-dropped'];
                    $response = Helper::ifApiSuccess($action, $packet);
                }
            } else {
                $packet = ['errorMessage' => 'service deactivated'];
                $response = Helper::ifValidationFalse($action, $packet);
            }               
        } else {
            $packet = $checkValidation;
            $response = Helper::ifValidationFalse($action, $packet);

        }

    	return $response;
    }
    public function cancelShipmentTcs($order){
        $store_id = $order['store_id'];
        $tracking_id = $order->order_tracking_id;
        $city = $order->city;
        $address = $order->address;
        $data = Helper::getShippingcityWithCredentialsDb($store_id, $address, $city);
        if($order && isset($order) && count($order) > 0){
            $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);

            $result = $client->call("CancelShipment", [
                'userName' => $data['credentials']['username'],
                'password' => $data['credentials']['password'],
                'consigneeNumber' => $tracking_id,
            ]);
            if ($result['CancelShipmentResult'] == "true") {
                $update_data = ['status' => 2];
                $order->update($update_data);

                $response = ['response' => true, 'successMessage' => 'Order has been cancelled from TCS'];
            } else {
                $response = ['response' => false, 'errorMessage' => 'Something wrong with TCS'];
            }
        }
        else {
            $response = ['response' => false, 'errorMessage' => 'No data found, traking data/payload'];
        }
        return $response;
    }
    // ship to kangaroo
    public function kangaroo($payload) {

        @file_put_contents('kangaroo.json', $payload, true);
        $kangaroo = json_decode( $payload->orderinfo , true)['dataPacket'];        
        $url = Config::get('urls.courier_urls.kangaroo');
        $store_id = $payload['store_id'];
        $order_id = $kangaroo['order_id'];

        if ($kangaroo['payment_method']['code'] == 'cod') {
            $order_total_price = Helper::getOrderTotalAmount($kangaroo['total']);
        } else {
            $order_total_price = 0;
        }

        $product_details = $kangaroo['cart'];         
        $products = Helper::productDetails($product_details); 

        $clientid = $payload['credentials']['clientid'];
        $pass = $payload['credentials']['password'];
        $cname = $kangaroo['customer']['firstname'] . ' ' . $kangaroo['customer']['lastname'];
        $caddress = $kangaroo['shipping_address']['address'] . ', ' . $kangaroo['shipping_address']['address_2']; 
        $cnumber = $kangaroo['customer']['telephone'];
        $amount = $order_total_price;
        $invoice = 'Order ID-' . $order_id;
        $pname = 'Products: ' . $products['name'];      // optional
        $pcode = 'QTY: ' . $products['qty'];      // optional
        $city = $payload['courier_city'];
        $orderType = 'cod';
        
        $curl_params = compact('clientid', 'pass', 'cname', 'caddress', 'cnumber', 'amount', 'invoice', 'pname', 'pcode', 'city', 'orderType');

        $curl_resp = json_decode(Helper::curlRequest($url, $curl_params), true);                
        if (isset($curl_resp['order id'])) {
            $tracking_id = $curl_resp['order id'];                        

            $check_order_exist = Shipping::where(compact('store_id', 'order_id'))->first();
            if ($check_order_exist) {

                $courier_url = Config::get('urls.courier_urls.kangaroo_track_order');        
                $update_data = ['courier_name' => 'kangaroo', 'order_tracking_id' => $tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
                $response = ['response' => true, 'order_id' => $order_id, 'tracking_id' => $tracking_id, 'courier_company' => 'kangaroo', 'courier_url' => $courier_url];

            } else {
                $response = ['response' => false, 'errorMessage' => 'order not exist'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with kangaroo'];
        }

        return $response;
    }

    // ship to call courier
    public function callcourier($payload)
    {   
        @file_put_contents('call_courier.json', $payload, true);
        
        $call_courier = json_decode( $payload->orderinfo , true)['dataPacket'];        
        $url = Config::get('urls.courier_urls.call_courier');        
        $loginId = $payload['credentials']['loginId'];

        $ConsigneeName = preg_replace("/[\s_]/", "-", $call_courier['customer']['firstname'] . '-' . $call_courier['customer']['lastname']);        
        $ConsigneeCellNo = preg_replace("/[\s_]/", "+", $call_courier['customer']['telephone']);
        $order_id = $call_courier['order_id'];
        $store_id = $payload['store_id'];
        $Address = urlencode(preg_replace("/[\s_]/", "-", $call_courier['shipping_address']['address'] . ', ' . $call_courier['shipping_address']['address_2']));
                
        $CodAmount = (int)Helper::getOrderTotalAmount($call_courier['total']);        
        $city = $payload['courier_city'];
        $productDetails = $call_courier['cart'];
        $products = Helper::productDetails($productDetails);        
        $Pcs = $products['qty'];
        $Weight = (isset($call_courier['weight']) && $call_courier['weight'] >= 0.5) ? $call_courier['weight'] : '0.5';        
        $Origin = $payload['origincity'];
        $Description = $products['name'];        
        $DestCityId = Helper::getCallcourierCityCode($city);     
        $ShipperName = preg_replace("/[\s_]/", "-", $payload['shipperInfo']['name']); 
        $ShipperCellNo = $payload['shipperInfo']['phone'];
        $ShipperLandLineNo = $payload['shipperInfo']['phone'];
        $ShipperAddress = urlencode(preg_replace("/[\s#]/", "-", $payload['shipperInfo']['address']));
        $ShipperEmail = $payload['shipperInfo']['email'];
        $ServiceTypeId = 7;
        $SelOrigin = 'Domestic';
        $SpecialHandling = 'false';
        $MyBoxId = urlencode(preg_replace('/[\s#]/', '-', '1 My Box ID'));
        $Holiday = 'false';
        $remarks = 'Remarks';
        $ShipperCity = $payload['shipperInfo']['shipper_city'];        
        $ShipperArea = $payload['shipperInfo']['shipper_area'];
        
        $url = $url . "?loginId=$loginId&ConsigneeName=$ConsigneeName&ConsigneeRefNo=$order_id&ConsigneeCellNo=$ConsigneeCellNo&Address=$Address&Origin=$Origin&DestCityId=$DestCityId&ServiceTypeId=$ServiceTypeId&Pcs=$Pcs&Weight=$Weight&Description=$Description&SelOrigin=$SelOrigin&CodAmount=$CodAmount&SpecialHandling=$SpecialHandling&MyBoxId=$MyBoxId&Holiday=$Holiday&remarks=$remarks&ShipperName=$ShipperName&ShipperCellNo=$ShipperCellNo&ShipperArea=$ShipperArea&ShipperCity=$ShipperCity&ShipperAddress=$ShipperAddress&ShipperLandLineNo=$ShipperLandLineNo&ShipperEmail=$ShipperEmail";
        
        /*$params = compact('loginId', 'ConsigneeName', 'ConsigneeRefNo', 'ConsigneeCellNo', 'Address', 'Origin', 'DestCityId', 'ServiceTypeId', 'Pcs', 'Weight', 'Description', 'SelOrigin', 'CodAmount', 'SpecialHandling', 'MyBoxId', 'Holiday', 'remarks', 'ShipperName', 'ShipperCellNo', 'ShipperArea', 'ShipperCity', 'ShipperAddress', 'ShipperLandLineNo', 'ShipperEmail');*/

        $curl_resp = json_decode(Helper::curlSentRequest($url, 'GET', [], [], true), true);        
        
        if ( isset($curl_resp['Response']) && $curl_resp['Response'] == "true" ) {

            $order_tracking_id = $curl_resp['CNNO'];
            $orderinfo = $payload['orderinfo'];

            $check_order_exist = Shipping::where(compact('store_id', 'order_id'))->first();

            if ($check_order_exist) {
                $courier_url = Config::get('urls.courier_urls.call_courier_track').$order_tracking_id;        
                $update_data = ['courier_name' => 'callcourier', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);

                $response = ['response' => true, 'order_id' => $order_id, 'tracking_id' => $order_tracking_id, 'courier_company' => 'callcourier', 'courier_url' => $courier_url];
            } else {
                $response = ['response' => false, 'errorMessage' => 'order not exist'];
            }

        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with CALL COURIER'];
        }

        return $response;
    }

    // ship to tcs
    public function tcs($payload)
    {   
        @file_put_contents('tcs.json', $payload, true);
        $tcs = json_decode( $payload->orderinfo , true)['dataPacket'];        
        if ($tcs['payment_method']['code'] == 'cod') {
            $order_total_price = Helper::getOrderTotalAmount($tcs['total']);
        } else {
            $order_total_price = 0;
        }

        $url = Config::get('urls.courier_urls.tcs');
        $address = $tcs['shipping_address']['address'] . ', ' . $tcs['shipping_address']['address_2']; 
        $city = $payload['courier_city'];
        $weight = (isset($tcs['weight']) && $tcs['weight'] >= 0.5) ? $tcs['weight'] : 0.5;  
        $store_id = $payload['store_id'];
        $order_id = $tcs['order_id'];
        $product_details = $tcs['cart'];         
        $products = Helper::productDetails($product_details); 
        $client = new nusoap_client($url, true);
        
        $result = $client->call("InsertData", [
            'userName' => $payload['credentials']['username'],
            'password' => $payload['credentials']['password'],
            'costCenterCode' => $payload['credentials']['costCenterCode'],
            'consigneeName' => $tcs['customer']['firstname'] . ' ' . $tcs['customer']['lastname'],
            'consigneeAddress' => $address,
            'consigneeMobNo' => $tcs['customer']['telephone'],
            'consigneeEmail' => $tcs['customer']['email'],
            'originCityName' => $payload['origincity'],
            'destinationCityName' => $city,
            'pieces' => $products['qty'],
            'weight' => $weight,
            'codAmount' => $order_total_price,
            'custRefNo' => $order_id,
            'productDetails' => 'Products: ' . $products['name'] . '-----QTY: ' . $products['qty'],
            'fragile' => 'No',
            'services' => 'O',
            'remarks' => 'QTY: ' . $products['qty'],
            'insuranceValue' => '0',
        ]);
        
        if (isset($result['InsertDataResult']) && $result['InsertDataResult'] !== 'Invalid City.' && $result['InsertDataResult'] !== 'Invalid User.' && $result['InsertDataResult'] !== 'Invalid Cost Center code.') {
            $order_tracking_id = $result['InsertDataResult'];                        

            $check_order_exist = Shipping::where(compact('store_id', 'order_id'))->first();

            if ($check_order_exist) {

                $courier_url = Config::get('urls.courier_urls.tcs_track');        
                $update_data = ['courier_name' => 'tcs', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
                $response = ['response' => true, 'order_id' => $order_id, 'tracking_id' => $order_tracking_id, 'courier_company' => 'tcs', 'courier_url' => $courier_url];

            } else {
                $response = ['response' => false, 'errorMessage' => 'order not exist'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with TCS'];
        }

        return $response; 
    }

    // ship to BLUE EX 
    public function bluex($payload) {

        @file_put_contents('bluex.json', $payload, true);
        $bluex = json_decode( $payload->orderinfo , true)['dataPacket'];        
        if ($bluex['payment_method']['code'] == 'cod') {
            $order_total_price = Helper::getOrderTotalAmount($bluex['total']);
        } else {
            $order_total_price = 0;
        }

        $url = Config::get('urls.courier_urls.blueex');
        $address = $bluex['shipping_address']['address'] . ', ' . $bluex['shipping_address']['address_2']; 
        $city = $payload['courier_city'];
        $weight = (isset($bluex['weight']) && $bluex['weight'] >= 0.5) ? $bluex['weight'] : 0.5;  
        $store_id = $payload['store_id'];
        $order_id = $bluex['order_id'];
        $product_details = $bluex['cart'];         
        $products = Helper::productDetails($product_details); 
        $client = new nusoap_client($url, true);
        
        $origin_city = Helper::getBluexCityCode($payload['origincity']);
        $destination_city = Helper::getBluexCityCode($city);

        set_time_limit(0);        
        $xml ="<?xml version='1.0' encoding='utf-8'?>
        <BenefitDocument>
            <AccessRequest>
            <DocumentType>1</DocumentType>
            <TestTransaction></TestTransaction>
                <ShipmentDetail>
                    <ShipperName>'".$payload['store_info']['name']."'</ShipperName>
                    <ShipperAddress>'". $payload['store_info']['address'] ."'</ShipperAddress>
                    <ShipperContact>". $payload['store_info']['phone'] ."</ShipperContact>
                    <ShipperEmail>". $payload['store_info']['email'] ."</ShipperEmail>
                    <ConsigneeName>". $bluex['customer']['firstname']." ".$bluex['customer']['lastname'] ."</ConsigneeName>
                    <ConsigneeAddress>". $address ."</ConsigneeAddress>
                    <ConsigneeContact>". $bluex['customer']['telephone'] ."</ConsigneeContact>
                    <ConsigneeEmail>". $bluex['customer']['email'] ."</ConsigneeEmail>
                    <CollectionRequired>Y</CollectionRequired>
                    <ProductDetail>Products: ".$products['name']."-----QTY: ".$products['qty'] ."</ProductDetail>
                    <ProductValue>". $order_total_price ."</ProductValue>
                    <OriginCity>". $origin_city ."</OriginCity>
                    <DestinationCountry>PK</DestinationCountry>
                    <DestinationCity>". $destination_city ."</DestinationCity>
                    <ServiceCode>BG</ServiceCode>
                    <ParcelType>N</ParcelType>
                    <Peices>". $products['qty'] ."</Peices>
                    <Weight>". $weight ."</Weight>
                    <Fragile>N</Fragile>
                    <ShipperReference>". $order_id ."</ShipperReference>
                    <InsuranceRequire>N</InsuranceRequire>
                    <InsuranceValue>0</InsuranceValue>
                    <ShipperComment>none</ShipperComment>
                </ShipmentDetail>
            </AccessRequest>
        </BenefitDocument>";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERPWD, $payload["credentials"]["username"] . ':' . $payload["credentials"]["password"]);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('xml' => $xml));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type=application/soap+xml', 'charset=utf-8'));
        $result = curl_exec ($curl);
        $result = json_decode(Helper::XMLtoJSON($result), true);
        
        if ($result['status'] == 1) {

            $order_tracking_id = $result['message'];                        

            $update_order = Shipping::where(compact('store_id', 'order_id'))->first();

            $courier_url = Config::get('urls.courier_urls.bluex_track').'?cn='.$order_tracking_id;     
            $update_data = ['courier_name' => 'bluex', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
            $update_order->update($update_data);
            $response = ['response' => true, 'order_id' => $order_id, 'tracking_id' => $order_tracking_id, 'courier_company' => 'bluex', 'courier_url' => $courier_url];

        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with Bluex'];
        }

        return $response;
    }

    // ship to leopard
    public function leopard($payload)
    {        
        @file_put_contents('leopard.json', $payload, true);
        $leoaprd = json_decode($payload->orderinfo, true)['dataPacket'];
        
        if ($leoaprd['payment_method']['code'] == 'cod') {
            $order_total_price = Helper::getOrderTotalAmount($leoaprd['total']);
        } else {
            $order_total_price = 0;
        }

        $url = Config::get('urls.courier_urls.leopard');        
        $address = $leoaprd['shipping_address']['address'] . ', ' . $leoaprd['shipping_address']['address_2']; 
        $city = $payload['courier_city'];
        $weight = (($leoaprd['weight'] && $leoaprd['weight'] >= 0.5) ? $leoaprd['weight'] : '0.5') * 1000;
        $store_id = $payload['store_id'];
        $order_id = $leoaprd['order_id'];
        $product_details = $leoaprd['cart'];         
        $products = Helper::productDetails($product_details);
        $consignment_email = $leoaprd['customer']['email'];  

        $origin_city = $payload['origincity'];
        $origin_city = $this->getLeopardCityID($origin_city);
        $destination_city = $this->getLeopardCityID($city);
    
        $curl_params = array(
            'api_key' => $payload['credentials']['api_key'],
            'api_password' => $payload['credentials']['api_password'],
            'booked_packet_weight' => $weight,  // weight in grams
            'booked_packet_vol_weight_w' => '',
            'booked_packet_vol_weight_h' => '',       // OPTIONAL
            'booked_packet_vol_weight_l' => '',
            'booked_packet_no_piece' => $products['qty'],
            'booked_packet_collect_amount' => $order_total_price,
            'booked_packet_order_id' => $order_id, // optional
            'origin_city' => $origin_city,    // $json['origincity']
            'destination_city' => $destination_city,
            'shipment_name_eng' => $payload['shipperInfo']['name'],
            'shipment_email' => str_replace(' ', '', $payload['shipperInfo']['email']),
            'shipment_phone' => $payload['shipperInfo']['phone'],
            'shipment_address' => $payload['shipperInfo']['address'],
            'consignment_name_eng' => $leoaprd['customer']['firstname'] . ' ' . $leoaprd['customer']['lastname'],
            'consignment_email' => $consignment_email,
            'consignment_phone' => $leoaprd['customer']['telephone'],
            'consignment_phone_two' => '',      //OPTIONAL
            'consignment_phone_three' => '',
            'consignment_address' => $address,
            'special_instructions' => 'Instructions'
        );
        
        $curl_response = Helper::curlRequest($url, $curl_params);
        $buffer = json_decode($curl_response, true);        

        if ($buffer['status'] == 1) {
        
            $order_tracking_id = $buffer['track_number'];
            $check_order_exist = Shipping::where(compact('store_id', 'order_id'))->first();

            if ($check_order_exist) {
                $courier_url = Config::get('urls.courier_urls.leopard_track_parcel');        
                $update_data = ['courier_name' => 'leopard', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
                $response = ['response' => true, 'order_id' => $order_id, 'tracking_id' => $order_tracking_id, 'courier_company' => 'leopard', 'courier_url' => $courier_url];
            } else {
                $response = ['response' => false, 'errorMessage' => 'order not exist'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => $buffer['error']];
        }
        
        return $response;
    }

    // ship to stallion
    public function stallion($payload)
    {    
        @file_put_contents('stallion.json', $payload, true);
        $stallion = json_decode($payload->orderinfo, true)['dataPacket'];
        
        if ($stallion['payment_method']['code'] == 'cod') {
            $order_total_price = Helper::getOrderTotalAmount($stallion['total']);
        } else {
            $order_total_price = 0;
        }

        $url = Config::get('urls.courier_urls.stallion');  
        $address = $stallion['shipping_address']['address'] . ', ' . $stallion['shipping_address']['address_2']; 
        $city = $payload['courier_city'];
        $weight = (isset($stallion['weight']) && $stallion['weight'] >= 0.5) ? $stallion['weight'] : 0.5;  
        $store_id = $payload['store_id'];
        $order_id = $stallion['order_id'];
        $product_details = $stallion['cart'];         
        $products = Helper::productDetails($product_details);     
        $filter = compact('store_id', 'order_id');
        $client = new nusoap_client($url, true);
        
        $result = $client->call('BookParcelWithshiperOrderId', [
            'username' => $payload['credentials']['username'],
            'password' => $payload['credentials']['password'],
            'ConsigneeAddress1' => $address,
            'ConsigneeName' => $stallion['customer']['firstname'] . ' ' . $stallion['customer']['lastname'],
            'ConsigneeCityid' => $city,
            'ConsigneePhone1' => $stallion['customer']['telephone'],
            'ItemType' => 'Products: ' . $products['name'] . '-----QTY: ' . $products['qty'],
            'PickupDate' => date('Y-m-d'),
            'SpecialInstruction' => 'instruction',
            'CODAmount' => (int)$order_total_price,
            'PickupAddressid' => '',
            'Hide' => 'show',
            'Quantity' => $products['qty'],
            'shiperOrderId' => $order_id
        ]);
        
        if(isset($result['BookParcelWithshiperOrderIdResult'])) {

            $order_tracking_id = $result['BookParcelWithshiperOrderIdResult'];                        

            $orderinfo = $payload['orderinfo'];
            $check_order_exist = Shipping::where($filter)->first();

            if ($check_order_exist) {
                $courier_url = $url;
                $update_data = ['courier_name' => 'stallion', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
                $response = ['response' => true, 'order_id' => $order_id, 'tracking_id' => $order_tracking_id, 'courier_company' => 'stallion', 'courier_url' => $courier_url];
            } else {
                $response = ['response' => false, 'errorMessage' => 'order not exist'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with STALLION'];
        }

        return $response;
    } 

    // get city id for leopard
    public function getLeopardCityID($city)
    {
        $city_id = 'self';
        $leopard_city_list = json_decode(file_get_contents('cities/leopard_city_list.json'), true);

        foreach ($leopard_city_list['city_list'] as $key => $value) {
            if ($value['name'] == ucfirst($city)) {
                $city_id = $value['id'];
                break;
            }
        }

        return $city_id;
    }
   
}