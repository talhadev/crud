<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\SendOrderSuccess;
use App\Jobs\SendOrderFailure;
use Config;
use Lang;
use Mail;
use Helper;
use nusoap_client;
// MODELS
use App\Models\Shipping;
use App\Models\Stores;
use App\Models\Orderfailure;
use function Sodium\add;

class ApiShippingController extends Controller
{
    public function shipped($payload)
    {   
        ini_set('max_execution_time', 300); // 60 * 5 (5mins)
        $timestamp = $payload['timestamp'];
        $order_info = $payload['dataPacket'];

        if (isset($order_info['orderinfo'])){
            $action = $payload['action'];
            $store_info = Stores::Select('technify_store_id', 'name', 'store_url', 'uuid')->where('technify_store_id', $order_info['ord erinfo']['store_id'])->first();
            $store_id = $order_info['orderinfo']['store_id'];
            $order_id = $order_info['orderinfo']['order_id'];
            $license_key = $store_info->uuid;
        } elseif (isset($order_info['order_id'])) {
            $action = $payload['action'];
            $license_key = $payload['license_key'];

            $store_info = Stores::Select('technify_store_id', 'name', 'store_url')->where('uuid', $license_key)->first();
            $store_id = $store_info->technify_store_id;
            $order_id = $order_info['order_id'];
        }

        if (!empty($order_info)) {

            $status = 1;
            $city = strtolower($order_info['shipping_address']['city']);            
            $address = strtolower($order_info['shipping_address']['address']);
            $check_order_shippied = Shipping::where(compact('store_id', 'order_id', 'status'))->first();        

            if (!$check_order_shippied) {
                
                $data = Helper::getCity($address, $city, $store_id); 
                
                if (!empty($data) && $data !== '') {

                    $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);

                    if ($get_store_spec && isset($get_store_spec['shippingdetails'])) {
                        // $order_info['orderinfo']['store_name'] = $get_store_spec['store_info']['name'];
                        // $order_info['orderinfo']['store_url'] = $get_store_spec['store_info']['url'];
                        //$order_info['customer']['email'] = str_replace(' ', '', $order_info['customer']['email']);

                        $city = strtolower($data['city']);
                        $address = strtolower($order_info['shipping_address']['address']);
                        $telephone = strtolower($order_info['customer']['telephone']);
                        $companies = $get_store_spec['shippingdetails']['variables']['credentials'];

                        foreach ($companies as $key => $value) {
                            if ($data['courier_company'] == $key) {
                                $credentials = $value;
                                $origincity = $get_store_spec['shippingdetails']['variables']['origincity'];
                                break;
                            }
                        }
                    }

                    if ($timestamp && $credentials && $origincity && $order_info) {
                        $shipping_json = [
                            'action' => 'shippingdetails',
                            'timestamp' => $timestamp,
                            'ifCityIsNull' => $data['city'],
                            'credentials' => $credentials,
                            'origincity' => $origincity,
                            'license_key' => $license_key,
                            'datapacket' => $order_info
                        ];

                        $courier_company = $data['courier_company']; 
                        $courier_response = $this->$courier_company($shipping_json);
                        
                        if ($courier_response['response']) {

                            $tracking_id = $courier_response['tracking_id'];
                            $order_id = $courier_response['order_id'];
                            $store_url = $courier_response['store_url'];

                            $courier_company = $courier_response['courier_company'];
                            $customer_email = $order_info['customer']['email'];
                            $data = ['order_id' => $order_id, 'tracking_id' => $tracking_id, 'courier_company' => $courier_company, 'courier_domain' => $courier_company];

                            $support_email = Stores::select('email', 'support_email')->where('technify_store_id', $store_id)->first();

                            if ($support_email) {
                                $emails = str_replace(" ", "", explode(',', $support_email->support_email));   // send emails for order success
                                foreach ($emails as $email) {
                                    dispatch(new SendOrderSuccess($data, $email));
                                }
                            }

                            $datapacket = ['shipped_to' => $courier_company, 'tracking_id' => $tracking_id, 'order_id' => $order_id, 'address' => $address, 'city' => $city, 'telephone' => $telephone, 'email' => $customer_email];

                            $payload = ['action' => 'updateOrder', 'response' => true, 'dataPacket' => $datapacket];
                            $url = $get_store_spec['store_info']['api_endpoint'];

                            Helper::curlRequest($url, json_encode($payload, true));

                            $sms_payload = ['store_id' => $store_id, 'order_id' => $order_id, 'tracking_id' => $tracking_id, 'courier_company' => $courier_company, 'telephone' => $telephone, 'store_name' => $store_info->name];

                            //$sms = Helper::sendSmsOnOrderShipped($sms_payload);

                            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['successMessage' => 'ORDER SUCCESSFULLY SHIPPED TO ' . strtoupper($courier_response['courier_company']) . ' CHECK EMAIL OR VISIT YOUR TECHNIFY DASHBOARD', 'courier_company' => strtoupper($courier_response['courier_company']), 'tracking_id' => $tracking_id]]);

                        } else {
                            $shipping_json = [
                                'action' => 'orderfailure',
                                'timestamp' => $timestamp,
                                'failure_address' => $order_info['shipping_address']['address'],
                                'failure_city' => $order_info['shipping_address']['city'],
                                'datapacket' => $order_info
                            ];


                            $order_id = $shipping_json['datapacket']['order_id'];
                            $store_id = $store_id;
                            $telephone = $shipping_json['datapacket']['customer']['telephone'];
                            $email = $shipping_json['datapacket']['customer']['email'];
                            $failure_address = $shipping_json['failure_address'];
                            $failure_city = $shipping_json['failure_city'];

                            $msg = $courier_response['errorMessage'] . ', ' . $this->processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json);

                            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                        }
                    }

                } else {

                    $shipping_json = [
                        'action' => 'orderfailure',
                        'timestamp' => $timestamp,
                        'failure_address' => $order_info['shipping_address']['address'],
                        'failure_city' => $order_info['shipping_address']['city'],
                        'datapacket' => $order_info
                    ];

                    $order_id = $order_id;
                    $tracking_id =
                    $store_id = $store_id;
                    $telephone = $shipping_json['datapacket']['customer']['telephone'];
                    $email = $shipping_json['datapacket']['customer']['email'];
                    $failure_address = $shipping_json['failure_address'];
                    $failure_city = $shipping_json['failure_city'];

                    $msg = $this->processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json);

                    $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
                }

            } else {
                $tracking_id = $check_order_shippied->order_tracking_id;
                $msg = 'Order already been shipped';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg, 'tracking_id' => $tracking_id]]);
            }

        } else {
            $msg = 'order does not exist';
            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
        }

        return $response;
    }

    public function bluex($payload)
    {
        @file_put_contents('bluex.json', json_encode($payload, true), true);
        $store_id = Stores::where('uuid', $payload['license_key'])->first();
        if ($store_id->technify_store_id == 100007) {
            $bluex = $payload['datapacket'];
            $license_key = $payload['license_key'];
            $store_info = Stores::Select('technify_store_id', 'name', 'store_url')->where('uuid', $license_key)->first();
            $store_id = $store_info->technify_store_id;
            $order_id = $bluex['order_id'];
            $store_url = $store_info->store_url;
            $result = Helper::bluexCompanion($payload);


        } else {
            $bluex = $payload['datapacket'];

            if (isset($bluex['order_id'])) {
                $license_key = $payload['license_key'];
                $store_info = Stores::Select('technify_store_id', 'name', 'store_url')->where('uuid', $license_key)->first();
                $store_id = $store_info->technify_store_id;
                $order_id = $bluex['order_id'];
                $store_url = $store_info->store_url;

            } elseif (isset($bluex['orderinfo'])) {

                $action = $payload['action'];
                $store_id = $bluex['orderinfo']['store_id'];
                $order_id = $bluex['orderinfo']['order_id'];
                $store_url = $bluex['orderinfo']['store_url'];
                $license_key = $payload['license_key'];
            }


            if (isset($bluex['orderinfo'])) {
                if ($bluex['payment_method'][0]['code'] == 'cod') {
                    $ordertype = $bluex['payment_method'][0]['code'];
                    $order_total_price = Helper::getOrderTotalAmount($bluex['total']);


                } else {
                    $order_total_price = 0;
                }
            } elseif (isset($bluex['order_id'])) {
                if ($bluex['payment_method']['code'] == 'cod') {
                    $ordertype = $bluex['payment_method']['code'];
                    $order_total_price = Helper::getOrderTotalAmount($bluex['total']);
                } else {
                    $order_total_price = 0;
                }

            }

            $address = $bluex['shipping_address']['address'] . ', ' . $bluex['shipping_address']['address_2'];
            $city = $payload['ifCityIsNull'];
            $order_id = $order_id;
            $productDetails = $bluex['cart'];
            $products = Helper::productDetails($productDetails);
            $weight = (isset($bluex['weight']) && $bluex['weight'] >= 0.5) ? $bluex['weight'] : 0.5;

            $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true)['store_info'];

            $origin_city = Helper::getBluexCityCode($payload['origincity']);
            $destination_city = Helper::getBluexCityCode($payload['ifCityIsNull']);

            set_time_limit(0);
            $url = Config::get('urls.courier_urls.blueex');
            $xml = "<?xml version='1.0' encoding='utf-8'?>
      	<BenefitDocument>
        	<AccessRequest>
        	<DocumentType>1</DocumentType>
        	<TestTransaction></TestTransaction>
            	<ShipmentDetail>
                	<ShipperName>'" . $get_store_spec['name'] . "'</ShipperName>
                	<ShipperAddress>'" . $get_store_spec['address'] . "'</ShipperAddress>
                	<ShipperContact>" . $get_store_spec['phone'] . "</ShipperContact>
                	<ShipperEmail>" . $get_store_spec['email'] . "</ShipperEmail>
                	<ConsigneeName>" . $bluex['customer']['firstname'] . " " . $bluex['customer']['lastname'] . "</ConsigneeName>
                	<ConsigneeAddress>" . $address . "</ConsigneeAddress>
                	<ConsigneeContact>" . $bluex['customer']['telephone'] . "</ConsigneeContact>
                	<ConsigneeEmail>" . $bluex['customer']['email'] . "</ConsigneeEmail>
                	<CollectionRequired>Y</CollectionRequired>
                	<ProductDetail>Products: " . $products['name'] . "-----QTY: " . $products['qty'] . "</ProductDetail>
                	<ProductValue>" . $order_total_price . "</ProductValue>
                	<OriginCity>" . $origin_city . "</OriginCity>
                	<DestinationCountry>PK</DestinationCountry>
                	<DestinationCity>" . $destination_city . "</DestinationCity>
                	<ServiceCode>BG</ServiceCode>
                	<ParcelType>N</ParcelType>
                	<Peices>" . $products['qty'] . "</Peices>
                	<Weight>" . $weight . "</Weight>
                	<Fragile>N</Fragile>
                	<ShipperReference>" . $order_id . "</ShipperReference>
                	<InsuranceRequire>N</InsuranceRequire>
                	<InsuranceValue>0</InsuranceValue>
                	<ShipperComment>none</ShipperComment>
            	</ShipmentDetail>
        	</AccessRequest>
      	</BenefitDocument>";


            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_USERPWD, $payload["credentials"]["username"] . ":" . $payload["credentials"]["password"]);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, array('xml' => $xml));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type=application/soap+xml', 'charset=utf-8'));

            $result = curl_exec($curl);
            $result = Helper::XMLtoJSON($result);


            $result = json_decode($result, true);
        }
        if ($result['status'] == 1) {

            $order_tracking_id = $result['message'];
            $order_id = $order_id;
            $store_id = $store_id;
            $store_url = $store_url;
            $mobile_number = $payload['datapacket']['customer']['telephone'];
            $orderinfo = json_encode($payload, true);

            $check_order_exist = Shipping::where('order_id', $order_id)->where('store_id', $store_id)->first();

            if ($check_order_exist) {
                $update_data = ['courier_name' => 'bluex', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
            } else {
                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'orderinfo' => $orderinfo, 'courier_status' => 'Not Available', 'customer_number' => $mobile_number, 'courier_name' => 'bluex', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                Shipping::create($data);
            }

            $response = ['response' => true, 'order_id' => $order_id, 'store_url' => $store_url, 'tracking_id' => $order_tracking_id, 'courier_company' => 'bluex'];

        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with Bluex please try again'];
        }

        return $response;
    }

    public function kangaroo($payload)
    {        
        @file_put_contents('kangaroo.json', json_encode($payload, true), true);
        $kangaroo = $payload['datapacket'];

        if (isset($kangaroo['orderinfo'])) {
            $action = $payload['action'];
            $store_id = $kangaroo['orderinfo']['store_id'];
            $order_id = $kangaroo['orderinfo']['order_id'];
            $store_url = $kangaroo['orderinfo']['store_url'];
            $license_key = $payload['license_key'];
        } elseif (isset($kangaroo['order_id'])) {

            $action = $payload['action'];
            $license_key = $payload['license_key'];
            $store_info = Stores::Select('technify_store_id', 'name', 'store_url')->where('uuid', $license_key)->first();
            $store_id = $store_info->technify_store_id;
            $order_id = $kangaroo['order_id'];
            $store_url = $store_info->store_url;

        }

        if (isset($kangaroo['orderinfo'])) {
            if ($kangaroo['payment_method'][0]['code'] == 'cod') {
                $ordertype = $kangaroo['payment_method'][0]['code'];
                $order_total_price = Helper::getOrderTotalAmount($kangaroo['total']);


            } else {
                $order_total_price = 0;
            }

        } elseif (isset($kangaroo['order_id'])) {

            if ($kangaroo['payment_method']['code'] == 'cod') {

                $ordertype = $kangaroo['payment_method']['code'];
                $order_total_price = Helper::getOrderTotalAmount($kangaroo['total']);


            } else {
                $ordertype = $kangaroo['payment_method']['code'];
                $order_total_price = Helper::getOrderTotalAmount($kangaroo['total']);

            }

        }

        $address = $kangaroo['shipping_address']['address'] . ', ' . $kangaroo['shipping_address']['address_2'];
        $order_id = $order_id;


        $productDetails = $kangaroo['cart'];
        $products = Helper::productDetails($productDetails);
//        if($store_id=='400001') {
        $url = 'http://kangaroo.pk/orderapi.php';

//        }else {
//            $url     = Config::get('urls.courier_urls.kangaroo');
//
//        }
        $clientid = $payload['credentials']['clientid'];
        $pass = $payload['credentials']['password'];
        $cname = $kangaroo['customer']['firstname'] . ' ' . $kangaroo['customer']['lastname'];
        $caddress = $address;

        $cnumber = $kangaroo['customer']['telephone'];
        $amount = $order_total_price;
        $invoice = $order_id;
        $pname = 'Products: ' . $products['name'];      // optional
        $pcode = 'QTY: ' . $products['qty'];      // optional
        $city = $payload['origincity'];
        $orderType = $ordertype;

        $curl_params = array(
            "clientid" => $clientid,
            "pass" => $pass,
            "cname" => $cname,
            "caddress" => $caddress,
            "cnumber" => $cnumber,
            "amount" => $amount,
            "invoice" => 'Order ID-' . $invoice,
            // "invoice" => 'Order ID-' . rand(10,100),
            "pname" => $pname,
            "pcode" => $pcode,
            "city" => $city,
            "orderType" => $orderType,
        );

        $curl_response = Helper::curlRequest($url, $curl_params);        
        $buffer = json_decode($curl_response, true);

        if (isset($buffer['order id'])) {
            $order_tracking_id = $buffer['order id'];
            $order_id = $order_id;
            $store_id = $store_id;
            $store_url = $store_url;
            $customer_number = $payload['datapacket']['customer']['telephone'];

            $orderinfo = json_encode($payload, true);

            $check_order_exist = Shipping::where('order_id', $order_id)->where('store_id', $store_id)->where('status', '2')->first();

            if ($check_order_exist && $check_order_exist->status == '2') {
                $update_data = ['order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
            } else {
                $courier_name = 'kangaroo'; $courier_status = 'Not Available'; $status = '1';
                $data = compact('store_id', 'order_id', 'orderinfo', 'courier_name', 'customer_number', 'courier_status', 'order_tracking_id', 'status', 'amount');                

                Shipping::create($data);
            }

            $response = ['response' => true, 'order_id' => $order_id, 'store_url' => $store_url, 'tracking_id' => $order_tracking_id, 'courier_company' => 'kangaroo'];

        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with kangaroo please try again'];
        }

        return $response;
    }

    public function leopard($payload)
    {
        file_put_contents('leopard.json', json_encode($payload, true), true);
        $leoaprd = $payload['datapacket'];
        if (isset($leoaprd['order_id'])) {
            $action = $payload['action'];
            $license_key = $payload['license_key'];
            $order_id = $leoaprd['order_id'];
            $store_info = Stores::Select('technify_store_id', 'name', 'store_url')->where('uuid', $license_key)->first();
            $store_name = $store_info->name;
            $store_id = $store_info->technify_store_id;
            $store_url = $store_info->store_url;
        } elseif (isset($leoaprd['orderinfo'])) {
            $action = $payload['action'];
            $store_id = $leoaprd['orderinfo']['store_id'];
            $order_id = $leoaprd['orderinfo']['order_id'];
            $store_name = $leoaprd['orderinfo']['store_name'];
            $store_url = $leoaprd['orderinfo']['store_url'];
            $license_key = $payload['license_key'];
        }


        $store_spec = json_decode(Helper::getStoreSpec($store_id), true);

        $url = Config::get('urls.courier_urls.leopard');


        $order_id = $order_id;

        $address = $leoaprd['shipping_address']['address'] . ', ' . $leoaprd['shipping_address']['address_2'];

        $consignment_email = $leoaprd['customer']['email'];

        if (isset($leoaprd['orderinfo'])) {
            if ($leoaprd['payment_method'][0]['code'] == 'cod') {
                $ordertype = $leoaprd['payment_method'][0]['code'];
                $order_total_price = Helper::getOrderTotalAmount($leoaprd['total']);


            } else {
                $order_total_price = Helper::getOrderTotalAmount($leoaprd['total']);
                $order_total_price = 0;
            }
        } elseif (isset($leoaprd['order_id'])) {
            if ($leoaprd['payment_method']['code'] == 'cod') {
                $ordertype = $leoaprd['payment_method']['code'];
                $order_total_price = Helper::getOrderTotalAmount($leoaprd['total']);


            } else {
                $order_total_price = Helper::getOrderTotalAmount($leoaprd['total']);
            }

        }


        $city = $payload['ifCityIsNull'];
        $productDetails = $leoaprd['cart'];
        $products = Helper::productDetails($productDetails);
        $weight = (($leoaprd['weight'] && $leoaprd['weight'] >= 0.5) ? $leoaprd['weight'] : '0.5') * 1000;
        $origin_city = $store_spec['shippingdetails']['variables']['origincity'];
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
            'shipment_name_eng' => $store_name,
            'shipment_email' => str_replace(' ', '', $store_spec['store_info']['email']),
            'shipment_phone' => $store_spec['store_info']['phone'],
            'shipment_address' => $store_spec['store_info']['address'],
            'consignment_name_eng' => $leoaprd['customer']['firstname'] . ' ' . $leoaprd['customer']['lastname'],
            'consignment_email' => 'shehriyarnadeemy@gmail.com',
            'consignment_phone' => $leoaprd['customer']['telephone'],
            'consignment_phone_two' => '',      //OPTIONAL
            'consignment_phone_three' => '',
            'consignment_address' => $address,
            'special_instructions' => 'Instructions'
        );

        $curl_response = Helper::curlRequest($url, $curl_params);
        $buffer = json_decode($curl_response, true);


        if ($buffer['status'] == 1) {
            $traking_no = $buffer['track_number'];
            $order_id = $order_id;
            $store_id = $store_id;
            $store_url = $store_url;
            $customer_number = $payload['datapacket']['customer']['telephone'];
            $orderinfo = json_encode($payload, true);

            $check_order_exist = Shipping::where('order_id', $order_id)->where('store_id', $store_id)->first();

            if ($check_order_exist) {
                $update_data = ['courier_name' => 'leopard', 'order_tracking_id' => $traking_no, 'status' => '1'];
                $check_order_exist->update($update_data);

            } else {
                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'orderinfo' => $orderinfo, 'courier_name' => 'leopard', 'customer_number' => $customer_number, 'courier_status' => 'Not Available', 'order_tracking_id' => $traking_no, 'status' => '1'];

                Shipping::create($data);
            }

            $response = ['response' => true, 'order_id' => $order_id, 'store_url' => $store_url, 'tracking_id' => $traking_no, 'courier_company' => 'leopard'];

        } else {
            $response = ['response' => false, 'errorMessage' => $buffer['error']];
        }

        return $response;
    }


    // for parsal shipped to call courier
    public function callcourier($payload)
    {   
        @file_put_contents('call_courier.json', json_encode($payload), true);
        $call_courier = $payload['datapacket'];

        $url = Config::get('urls.courier_urls.call_courier');
        $store_id = Helper::getStoreInfo($payload['license_key'], 'technify_store_id')['storeInfo']['technify_store_id'];
        $store_spec = json_decode(Helper::getStoreSpec($store_id), true)['store_info'];        
        $loginId = $payload['credentials']['loginId'];
        $customer_name = preg_replace("/[\s_]/", "-", $call_courier['customer']['firstname'] . '-' . $call_courier['customer']['lastname']);
        $telephone = preg_replace("/[\s_]/", "+", $call_courier['customer']['telephone']);
        $order_id = $call_courier['order_id'];
        $address = preg_replace("/[\s_]/", "-", $call_courier['shipping_address']['address'] . ', ' . $call_courier['shipping_address']['address_2']);
        // $address = urlencode($call_courier['shipping_address']['address'].''.$call_courier['shipping_address']['address_2']);
        $order_total_price = (int)Helper::getOrderTotalAmount($call_courier['total']);
        $city = $payload['ifCityIsNull'];
        $productDetails = $call_courier['cart'];
        $products = Helper::productDetails($productDetails);
        $qty = $products['qty'];
        $weight = ($call_courier['weight'] && $call_courier['weight'] >= 0.5) ? $call_courier['weight'] : '0.5';
        $origin_city = 'Karachi';
        $desc = $products['name'];
        $destination_city = Helper::getCallcourierCityCode($city);
        $shipper_name = preg_replace("/[\s_]/", "-", $store_spec['name']);
        $shipper_cell = $store_spec['phone'];
        $shipper_addr = preg_replace("/[\s#]/", "-", $store_spec['address']);
        $shipper_email = $store_spec['email'];
        $ShipperArea = 534;
        $ShipperCity = 2;  

        $url = $url . "?loginId=$loginId&ConsigneeName=$customer_name&ConsigneeRefNo=$order_id&ConsigneeCellNo=$telephone&Address=$address&Origin=$origin_city&DestCityId=$destination_city&ServiceTypeId=7&Pcs=$qty&Weight=$weight&Description=$desc&SelOrigin=Domestic&CodAmount=$order_total_price&SpecialHandling=false&MyBoxId=1%20My%20Box%20ID&Holiday=false&remarks=Remarks&ShipperName=$shipper_name&ShipperCellNo=$shipper_cell&ShipperArea=$ShipperArea&ShipperCity=$ShipperCity&ShipperAddress=$shipper_addr&ShipperLandLineNo=$shipper_cell&ShipperEmail=$shipper_email";        
        /*$params = ['loginId' => $loginId, 'ConsigneeName' => $customer_name, 'ConsigneeRefNo' => $order_id, 'ConsigneeCellNo' => $telephone, 'Address' => $address, 'Origin' => $origin_city, 'DestCityId' => $destination_city, 'ServiceTypeId' => 7, 'Pcs' => $qty, 'Weight' => $weight, 'Description' => $desc, 'SelOrigin' => 'Domestic', 'CodAmount' => $order_total_price, 'SpecialHandling' => false, 'MyBoxId' => '1%20My%20Box%20ID', 'Holiday' => false, 'remarks' => 'Remarks', 'ShipperName' => $shipper_name, 'ShipperCellNo' => $shipper_cell, 'ShipperArea' => 1, 'ShipperCity' => 1, 'ShipperAddress' => $shipper_addr, 'ShipperLandLineNo' => $shipper_cell, 'ShipperEmail' => $shipper_email];*/

        $curl_resp = json_decode(Helper::curlSentRequest($url, 'GET'), true);

        if ($curl_resp['Response'] == "true") {

            $order_tracking_id = $curl_resp['CNNO'];
            $order_id = $call_courier['order_id'];
            $store_url = $store_spec['url'];
            $orderinfo = json_encode($payload, true);

            $check_order_exist = Shipping::where('order_id', $order_id)->where('store_id', $store_id)->first();

            if ($check_order_exist) {
                $update_data = ['courier_name' => 'callcourier', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
            } else {
                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'orderinfo' => $orderinfo, 'courier_name' => 'callcourier', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                Shipping::create($data);
            }

            $response = ['response' => true, 'order_id' => $order_id, 'store_url' => $store_url, 'tracking_id' => $order_tracking_id, 'courier_company' => 'callcourier'];
        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with CALL COURIER please try again'];
        }

        return $response;
    }

    // for parsal shipped to fedex
    public function fedex($payload)
    {
        $$response = ['response' => false, 'errorMessage' => 'fedex not integrated at technfiy'];

        return $response;
    }

    // parsal shipped to TCS
    public function tcs($payload)
    {
        @file_put_contents('tcs.json', json_encode($payload, true), true);


        $tcs = $payload['datapacket'];
        if ($tcs['payment_method']['code'] == 'cod') {
            $order_total_price = Helper::getOrderTotalAmount($tcs['total']);
        } else {
            $order_total_price = 0;
        }

        $address = $tcs['shipping_address']['address'] . ', ' . $tcs['shipping_address']['address_2'];
        $city = $payload['ifCityIsNull'];
        $order_id = $tcs['order_id'];
        $productDetails = $tcs['cart'];

        $products = Helper::productDetails($productDetails);

        $client = new nusoap_client(Config::get('urls.courier_urls.tcs'), true);

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
            'pieces' => 1,
            'weight' => 0.5,
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
            $order_id = $tcs['order_id'];
            $license_key = $payload['license_key'];
            $store_info = Stores::select('technify_store_id', 'store_url')->where('uuid', $license_key)->first();
            $store_url = $store_info->store_url;
            $store_id = $store_info->technify_store_id;


            $orderinfo = json_encode($payload, true);

            $check_order_exist = Shipping::where('order_id', $order_id)->where('store_id', $store_id)->first();

            if ($check_order_exist) {
                $update_data = ['courier_name' => 'tcs', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                $check_order_exist->update($update_data);
            } else {
                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'orderinfo' => $orderinfo, 'courier_name' => 'tcs', 'courier_status' => 'Not Available', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];
                Shipping::create($data);
            }

            $response = ['response' => true, 'order_id' => $order_id, 'tracking_id' => $order_tracking_id, 'store_url' => $store_url, 'courier_company' => 'tcs'];
        } else {
            $response = ['response' => false, 'errorMessage' => 'Something wrong with TCS please try again'];
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

    // process of order failure
    public function processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json)
    {
        $support_email = Stores::select('email', 'support_email')->where('technify_store_id', $store_id)->first();
        if ($support_email) {
            $emails = str_replace(" ", "", explode(',', $support_email->support_email));   // send emails for order failure
            $check_order_exist = Orderfailure::where('order_id', $order_id)->where('store_id', $store_id)->first();
            if (!$check_order_exist) {

                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'failure_address' => $failure_address, 'failure_city' => $failure_city, 'telephone' => $telephone, 'email' => $email, 'status' => '0', 'orderinfo' => json_encode($shipping_json)];

                Orderfailure::create($data);

            } else {

                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'failure_address' => $failure_address, 'failure_city' => $failure_city, 'telephone' => $telephone, 'email' => $email, 'status' => '0', 'orderinfo' => json_encode($shipping_json)];
                $update_order_failure = Orderfailure::where('order_id', $order_id)->where('store_id', $store_id)->first();
                $update_order_failure->update($data);

            }

            foreach ($emails as $email) {
                $data = ['order_id' => $order_id, 'app_url' => Config::get('urls.app_urls.app_url')];
                dispatch(new SendOrderFailure($data, $email));
            }

            $response = 'SOMETHING WRONG IN YOUR ORDER, PLEASE CHECK EMAIL OR VISIT YOUR TECHNIFY DASHBOARD';
        } else {
            $response = 'Store not authorize for Technify';
        }

        return $response;
    }

    public function cancelShipmentKangaroo($payload)
    {

        $cancel_kangaroo = $payload;
        $order_id = $cancel_kangaroo['order_id'];
        $store_id = $cancel_kangaroo['store_id'];
        $filters = ['order_id' => $order_id, 'store_id' => $store_id];
        $traking_data = Shipping::where($filters)->first();

        if ($cancel_kangaroo && isset($cancel_kangaroo) && count($traking_data) > 0) {
            $traking_no = $traking_data->order_tracking_id;
            if ($store_id == '400001') {

                $url = 'http://stagging.kangaroo.pk/cancelapi.php';
            } else {
                $url = Config::get('urls.courier_urls.kangaroo_cacnel');
            }
            $params = array(
                'clientid' => $cancel_kangaroo['credentials']['clientid'],
                'pass' => $cancel_kangaroo['credentials']['password'],
                'orderid' => $traking_no,

            );
            $kngro_resp = json_decode(Helper::curlRequest($url, $params), true);

            if ($kngro_resp['orderresponse'] == true) {

                $update_data = ['status' => '2'];
                $traking_data->update($update_data);

                $response = ['response' => true, 'successMessage' => 'Order has been cancelled from Kangaroo'];
            } else {
                $response = ['response' => false, 'errorMessage' => 'Something wrong with Kangaroo'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'No data found, traking data/payload'];
        }

        return $response;
    }


    public function cancelShipment($payload)
    {


        $action = $payload['action'];
        $cncl_shpmnt = $payload['datapacket'];
        $license_key = $payload['license_key'];
        $store_info = Stores::select('technify_store_id', 'uuid')->where('uuid', $license_key)->first();

        $store_id = $store_info->technify_store_id;
        $order_id = $cncl_shpmnt['order_id'];

        $filter = ['store_id' => $store_id, 'order_id' => $order_id, 'status' => 1];
        $shipping = Shipping::where($filter)->first();

        if ($shipping) {

            $courier_company = $shipping->courier_name;
            $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);
            $companies = isset($get_store_spec['shippingdetails']['variables']['credentials']) ? $get_store_spec['shippingdetails']['variables']['credentials'] : [];
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

        } else {
            $msg = 'No data found or order already cancelled order already cancelled';
            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
        }

        return $response;
    }

    public function cancelshipmentTcs($payload)
    {
        $cancel_tcs = $payload;
        $order_id = $cancel_tcs['order_id'];
        $store_id = $cancel_tcs['store_id'];

        $filters = ['order_id' => $order_id, 'store_id' => $store_id];
        $traking_data = Shipping::where($filters)->first();

        if ($cancel_tcs && isset($cancel_tcs) && count($traking_data) > 0) {
            $tracking_no = $traking_data->order_tracking_id;

            $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);

            $result = $client->call("CancelShipment", [
                'userName' => $cancel_tcs['credentials']['username'],
                'password' => $cancel_tcs['credentials']['password'],
                'consigneeNumber' => $tracking_no,
            ]);

            if ($result['CancelShipmentResult'] == "true") {
                $update_data = ['status' => 2];
                $traking_data->update($update_data);

                $response = ['response' => true, 'successMessage' => 'Order has been cancelled from TCS'];
            } else {
                $response = ['response' => false, 'errorMessage' => 'Something wrong with TCS'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'No data found, traking data/payload'];
        }

        return $response;
    }
}