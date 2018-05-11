<?php

namespace App\Http\Helpers;

use Carbon\Carbon;
use Config;
use DateTime;
use App\Models\logs;
use Helper;
use SimpleXMLElement;
use Illuminate\Support\Facades\Lang;
// Models
use App\Models\Stores;
use App\Models\Shipping;
use App\Models\Settings;
use App\Models\ShippingSettings;

class AppHelper{

    // constant api response
    public static function constantResponse($action, $statusCode, $errMsg)
    {
        return [
            'action'       => $action,
            'timestamp'    => Carbon::now()->toDateTimeString(),
            'statusCode'   => $statusCode,
            'errorMessage' => $errMsg,
        ];
    }

    // create constant request
    public static function curlConstantRequest($action)
    {
        return [
            'action'    => $action,
            'timestamp' => Carbon::now()->toDateTimeString()
        ];
    }

    // generate randon code only number or letter or string(mix)
    // number for numbers, letter for letters and string for both
    public static function generateRandomCode($checkFormat, $length) {

        $generateCodeFormat = AppHelper::getCodeFormat($checkFormat);
        $characters = $generateCodeFormat;
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public static function getPieces($orderinfo){      //for calculating products in a cart
          $count=0;
        foreach ($orderinfo as $item){
            $count = $count + 1;

        }
        return $count;
    }

    public static function getCodeFormat($checkFormat)
    {
        if( $checkFormat == 'number' ) {
            $format = '0123456789';
        } elseif( $checkFormat == 'letters' ) {
            $format = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } else {
            $format = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        return $format;
    }

    public static function addLogStach($payload)
    {
        $logstash_url  = Config::get('urls.app_urls.logstash_url');
        $logstash_port = Config::get('urls.app_ports.logstash_port');

        $msg = json_encode($payload, true);
        $payload = array_merge(['Project' => 'smsModule'], [ 'message' => $msg]);

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); //Create the socket
        $len = strlen($msg);

        socket_sendto($socket, $msg, $len, 0, $logstash_url, $logstash_port);
        socket_close($socket);

        return;
    }

    // get store specification


    public static function getStoreCityList($store_id){

        $url = Config::get('urls.cities_urls.store_city').$store_id.'.json';
     return $url;
         $store_cities = file_get_contents($url);

        if($store_cities) {
            $response = $store_cities;
        } else {
            $response = $store_cities;
        }

        return $response;
    }


    // get store specification on local
    public function getStoreSpecLocal($store_id) {
        $file = "stores/" .$store_id. "sample.json";
        $store_spec = file_get_contents($file);

        return $store_spec;
    }

    // get Order Totoal amount
    public static function getOrderTotalAmount($total_amount)
    {
        if(is_array($total_amount)) {
            foreach ($total_amount as $key => $value) {

                if ($value['title'] == "Total") {

                    $total_price = $value['value'];
                    break;
                }
            }
        }else{
            $total_price=$total_amount;
        }

        return $total_price;
    }

    // get productDetails (qty and names)
    public static function productDetails(array $productDetails)
    {
        $pname = ''; $qty = 0;
        foreach ($productDetails as $key => $pdetails) {
            $pname = $pname.$pdetails['name'].' ';
            $qty   = $qty+$pdetails['quantity'];
        }
        $pname = rtrim($pname);
        $data['name'] = str_replace(' ', ',', $pname);
        $data['qty']  = $qty;
        return $data;
    }

    public static function GetStoreNameByIDOrEmail($store_id_or_email)
    {
        if(filter_var($store_id_or_email, FILTER_VALIDATE_EMAIL)){
            $store_name = Stores::select('name')->where('email', $store_id_or_email)->first();
        } else {
            $store_name = Stores::select('name')->where('technify_store_id', $store_id_or_email)->first();
        }

        return (isset($store_name->name)) ? $store_name->name : 'invalid store ID or Name';
    }

    // return current timestamp
    public static function datetime() {
        $date = new DateTime();
        $timestamp = $date->format('Y-m-d H:i:s');
        // fedex
        // $timestamp = $date->format('Y-m-dTH:i:s');
        // return $timestamp = "2017-05-26T16:57:44";
        //# fedex
        return $timestamp;
    }

    // get courier company agaisnt city
    public static function getcity($address, $city, $store_id) { 
        
        $address = strtolower($address); $city = strtolower($city); 
        $data = [];
        $json = json_decode(Helper::getStoreSpec($store_id), true);  
        if( isset($json['shippingdetails']) ) {
            $cities = $json['shippingdetails']['variables']['location'];            
            $get_else_position = Helper::getElsePosition($cities);
            
            if($get_else_position!==false && (@include("cities/".$store_id.".php"))) {
                $cities[$get_else_position]["cities"] = array_map('strtolower', include("cities/".$store_id.".php"));
            }

            foreach ($cities as $key => $value) {

                foreach ($value['cities'] as $k => $v) {
                    
                    if( strpos($city, $v) || $city == $v || strpos($city, $v) === 0 || strpos($address, $v) || $address == $v ) {

                        $data['city'] = strtolower($v);
                        $data['courier_company'] = strtolower($cities[$key]['courier_name']);

                        break 2;
                    }                    
                }                
            }
        }
        
        return $data;
    }
    public static function getStoreSpec($store_id) {
        $url = Config::get('urls.spec_urls.store_spec').$store_id.'.json';
        $store_spec = @file_get_contents($url);
        $response = $store_spec;

        return $response;
    }
    // get courier company agaisnt city from file
    public static function getShippingcityWithCredentialsDb($store_id, $address, $city)
    {
        $address = strtolower($address);
        $city = strtolower($city);
        $json = Helper::getStoreSpecDb($store_id);
//        $decode=[];

        foreach ($json as $settings) {
            $location = json_decode($settings->locations, true);
            $data = ['response' => false];
            if (isset($location['shippingdetails'])) {
                $variables = $location['shippingdetails']['variables'];
                $cities = $variables['location'];
                $get_else_position = Helper::getElsePosition($cities);
                if($get_else_position!==false && (@include("cities/".$store_id.".php"))) {
                    $cities[$get_else_position]["cities"] = array_map('strtolower', include("cities/".$store_id.".php"));
                  
                }
                    foreach ($cities as $key => $value) {

                        foreach ($value['cities'] as $k => $v) {

                            if (strpos($city, $v) || $city == $v || strpos($city, $v) === 0 || strpos($address, $v) || $address == $v) {

                                $data['response'] = true;
                                $data['city'] = strtolower($v);
                                $data['origincity'] = strtolower($variables['origincity']);
                                $data['courier_company'] = strtolower($cities[$key]['courier_name']);
                                $data['credentials'] = $variables['credentials'][$data['courier_company']];
                                $data['shipperInfo'] = $location['store_info'];
                                break 2;
                            }
                        }
                    }
                }


        } 
        return $data;
    }
    public static function getStoreSpecDb($store_id) {
//        dd($store_id);
//        $filter = ['store_id' , $store_id];
        $db = Settings::select('locations')->where('store_id' , $store_id)->get();

        return $db;


    }



    // ORIGNAL get courier company against city
    public static function getShippingcityWithCredentials($store_id, $address, $city) {

        $address = strtolower($address); $city = strtolower($city);
        $json = json_decode(Helper::getStoreSpec($store_id), true);
        $data = ['response' => false];
        if( isset($json['shippingdetails']) ){
            $variables = $json['shippingdetails']['variables'];
            $cities = $variables['location'];
            $get_else_position = Helper::getElsePosition($cities);
            if($get_else_position!==false && (@include("cities/".$store_id.".php"))) {
                $cities[$get_else_position]["cities"] = array_map('strtolower', include("cities/".$store_id.".php"));
            }

            foreach ($cities as $key => $value) {

                foreach ($value['cities'] as $k => $v) {

                    if( strpos($city, $v) || $city == $v || strpos($city, $v) === 0 || strpos($address, $v) || $address == $v ) {

                        $data['response'] = true;
                        $data['city'] = strtolower($v);
                        $data['origincity'] = strtolower($variables['origincity']);
                        $data['courier_company'] = strtolower($cities[$key]['courier_name']);
                        $data['credentials'] = $variables['credentials'][$data['courier_company']];
                        $data['shipperInfo'] = $json['store_info'];

                        break 2;
                    }
                }
            }
        }

        return $data;
    }

    public static function bluexCompanion($payload)
    {


        file_put_contents('bluex.json', json_encode($payload, true), true);

        if ($payload['datapacket']['order_status']['order_status_id'] == 27) {
            $service_code = "BT";

        } elseif ($payload['datapacket']['order_status']['order_status_id'] == 3){

            $service_code = "BE";
        }


        $bluex = $payload['datapacket'];
        $license_key = $payload['license_key'];
        $store_info =Stores::Select('technify_store_id','name','store_url')->where('uuid',$license_key)->first();
        $store_id = $store_info->technify_store_id;
        $store_url = $store_info->store_url;


        if( $bluex['payment_method']['code'] == 'cod' ) {
            $order_total_price = Helper::getOrderTotalAmount($bluex['total']);
        } else {
            $order_total_price = 0;
        }
        $address = $bluex['shipping_address']['address'].', '.$bluex['shipping_address']['address_2'];
        $city = $payload['ifCityIsNull'];
        $order_id = $bluex['order_id'];
        $productDetails = $bluex['cart'];
        $products = Helper::productDetails($productDetails);
        $weight = (isset($bluex['weight']) && $bluex['weight'] >= 0.5) ? $bluex['weight'] : 0.5;

        $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true)['store_info'];

        $origin_city = Helper::getBluexCityCode($payload['origincity']);

        $destination_city = Helper::getBluexCityCode($payload['ifCityIsNull']);

        set_time_limit(0);
        $url = Config::get('urls.courier_urls.blueex');
        $xml ="<?xml version='1.0' encoding='utf-8'?>
      	<BenefitDocument>
        	<AccessRequest>
        	<DocumentType>1</DocumentType>
        	<TestTransaction></TestTransaction>
            	<ShipmentDetail>
                	<ShipperName>'".$get_store_spec['name']."'</ShipperName>
                	<ShipperAddress>'". $get_store_spec['address'] ."'</ShipperAddress>
                	<ShipperContact>". $get_store_spec['phone'] ."</ShipperContact>
                	<ShipperEmail>". $get_store_spec['email'] ."</ShipperEmail>
                	<ConsigneeName>". $bluex['customer']['firstname']." ".$bluex['customer']['lastname'] ."</ConsigneeName>
                	<ConsigneeAddress>". $address ."</ConsigneeAddress>
                	<ConsigneeContact>". $bluex['customer']['telephone'] ."</ConsigneeContact>
                	<ConsigneeEmail>". $bluex['customer']['email'] ."</ConsigneeEmail>
                	<CollectionRequired>Y</CollectionRequired>
                	<ProductDetail>Products: ".$products['name']."-----QTY: ".$products['qty'] ."</ProductDetail>
                	<ProductValue>". $order_total_price ."</ProductValue>
                	<OriginCity>". $origin_city."</OriginCity>
                	<DestinationCountry>PK</DestinationCountry>
                	<DestinationCity>". $destination_city ."</DestinationCity>
                	<ServiceCode>".$service_code."</ServiceCode>
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
        curl_setopt($curl, CURLOPT_URL, $url );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($curl, CURLOPT_USERPWD, $payload["credentials"]["username"].":".$payload["credentials"]["password"]);
        curl_setopt($curl, CURLOPT_POST, 1 );
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('xml' => $xml) );
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type=application/soap+xml', 'charset=utf-8'));

        $result = curl_exec($curl);

        $result = Helper::XMLtoJSON($result);


        $result = json_decode($result, true);
        return $result;

    }


    public static function getCourier($address,$city, $store_id){

        $address = strtolower($address); $city = strtolower($city);
        $courier_settings = Settings::where('store_id',$store_id)->get();
        $data=[];

        foreach($courier_settings as $settings) {
            $logix = json_decode($settings->logix, true);
            $cities = $logix['domestic']['include_city'];


            if(strcmp($cities[0],'All cities')==0) {


                    $cityList =Helper::getStoreCityList($store_id);

                foreach ($cityList as $key => $value) {

                    if ($city == strtolower($value) || $address == strtolower($value)) {
                        $data['city'] = strtolower($value);
                        $data['courier_company'] = strtolower($settings->courier_name);
                        break;
                    }
                }



            } else {


                foreach ($cities as $key => $value) {

                    if ($city == strtolower($value) || $address == strtolower($value)) {

                        $data['city'] = strtolower($value);
                        $data['courier_company'] = strtolower($settings->courier_name);
                        break;
                    }
                }

            }
        }
        return $data;

    }

    public static function curlRequestUpdateAndProceed($returntransfer, $url, $useragent, $payload, $username, $password){

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => $returntransfer,
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => $useragent,
            CURLOPT_POST           => 'POST',
            CURLOPT_USERPWD => $username.':'.$password,
            CURLOPT_POSTFIELDS     => json_encode($payload)
        ));$resp = json_decode(curl_exec($curl), true);
        curl_close($curl);

        return $resp;
    }

    public static function curlRequestWithHeaders($url, $payload, $header){

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
            CURLOPT_POST           => 'POST',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER => array($header),
        ));

        $resp = curl_exec($curl);
        curl_close($curl);

        return $resp;



}
    public static function curlRequestWithHeaders2($url,$payload,$auth_token,$content_type){

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_URL            => $url,
            CURLOPT_POST           => 'POST',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER => array(
                $auth_token,$content_type

            ),
        ));

        $resp=curl_exec($curl);
        $err = curl_error($curl); 
        curl_close($curl);
        return $resp;



    }

  public static function curlRequestWithHeadersGET($url,$header){
      $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
              $header
          ),
      ));

      $response = json_decode(curl_exec($curl),true);
      return $response;

  }



    // get else position in multidimesional array
    public static function getElsePosition($cities)
    {

        foreach ($cities as $key => $value) {

            foreach ($value['cities'] as $k => $v) {

                if($v == 'else'){
                    return $key;

                }
                break;
            }
        }

        return false;
    }

    // if APi 200 response packet
    public static function ifApiSuccess($action, $datapacket)
    {        
        $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $datapacket]);
        return $response;
    }
    
    // if validation false
    public static function ifValidationFalse($action, $checkValidation)
    {
        $msg = $checkValidation['errorMessage'];
        $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);

        return $response;
    }

    public static function getCityCode($city)
    {   
        $get_city_with_code = json_decode(@file_get_contents('cities/city_with_code.json'), true);
        $city_code = '';
        foreach ($get_city_with_code as $key => $code) {
            if( strtolower($city) == strtolower($key) ) {
                $city_code = $code;
                break;
            }
        }

        return $city_code;
    }
    // get bluex city code
    public static function getBluexCityCode($city)
    {
        $get_city_with_code = json_decode(@file_get_contents('cities/city_with_code.json'), true);
        $city_code = '';
        foreach ($get_city_with_code as $key => $code) {
            if( strtolower($city) == strtolower($key) ) {
                $city_code = $code;
                break;
            }
        }

        return $city_code;
    }

    // get call courier city code
    public static function getCallcourierCityCode($city)
    {
        $get_city_with_code = json_decode(@file_get_contents('cities/city_with_code_callcourier.json'), true);
        $city_code = '';
        foreach ($get_city_with_code as $key => $value) {
            if( strtolower($value['CityName']) == strtolower($city) ) {
                $city_code = $value['CityID'];
                break;
            }
        }
        
        return $city_code;
    }

    // convert xml to json
    public static function XMLtoJSON($xml)
    {
        $xml = str_replace(array("\n", "\r", "\t"), '', $xml);
        $xml = trim(str_replace('"', "'", $xml));
        $simpleXml = simplexml_load_string($xml);

        return stripslashes(json_encode($simpleXml));
    }

    public static function curlRequest($url, $payload)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTFIELDS => $payload
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $response;

    }

    // curl with basis authentication
    public static function curlRequestWithBasicAuth($url, $payload, $username, $password)
    {          
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_USERPWD => $username.':'.$password,
            CURLOPT_POSTFIELDS => json_encode($payload)
        ));
        $curl_response = curl_exec($curl);

        $err = curl_error($curl);
        curl_close($curl);
        return $curl_response;
    }

    public static function GetCNDetailsByReferenceNumberLeopard($tracking_id)
    {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'http://new.leopardscod.com/webservice/trackBookedPacket/format/json/');  // Write here Test or Production Link
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, array(
            'api_key' => 'BC8A745A2B7DA612EFA7E26B96E8E829',
            'api_password' => 'A?(.>H5WL2MF9GU',
            'track_numbers' => $tracking_id                      // E.g. 'XXYYYYYYYY' OR 'XXYYYYYYYY,XXYYYYYYYY,XXYYYYYY' 10 Digits each number
        ));

        $buffer = curl_exec($curl_handle);
        curl_close($curl_handle);
        $result = json_decode($buffer, true);

        foreach ($result as $res) {
            $table = $result['packet_list'][0];


            $data = [
                'booked_packet_id'     => $table['booked_packet_id'],
                'booking_date'         => $table['booking_date'],
                'trackingNumber'       => $table['track_number'],
                'trackingNumberShort'  => $table['track_number_short'],
                'booked_packet_weight'   => $table['booked_packet_weight'],
                'booked_packet_no_piece' => $table['booked_packet_no_piece'],
                'booked_packet_collect_amount' => $table['booked_packet_collect_amount'],
                'booked_packet_order_id' => $table['booked_packet_order_id'],
                'origin_country_name' => $table['origin_country_name'],
                'origin_city_name' => $table['origin_city_name'],
                'destination_city_name' => $table['destination_city_name'],
                'shipment_name_eng' => $table['shipment_name_eng'],
                'shipment_email' => $table['shipment_email'],
                'shipment_phone' => $table['shipment_phone'],
                'shipment_address' => $table['shipment_address'],
                'consignment_name_eng' => $table['consignment_name_eng'],
                'consignment_email' => $table['consignment_email'],
                'consignment_phone' => $table['consignment_phone'],
                'consignment_address' => $table['consignment_address'],
                'special_instructions' => $table['special_instructions'],
                'booked_packet_status' => $table['booked_packet_status'],
                'tracking_detail'=> $table['Tracking Detail']

            ];
        }


        return $data;


    }

    public static  function GetCNDetailsByReferenceNumberTCS($order_id)
    {

        $params = ['m2.cod', 'police123'];
        $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);
        $result = $client->call("GetCNDetailsByReferenceNumber", [
            'userName' => $params[0],
            'password' => $params[1],
            'customerReferenceNo' => $order_id,
        ]);

        foreach ((array)$result as $res) {
            $table = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table'];
            $data = [
                'CN' => $table['ConsignmentNumber'],
                'CustomerRef' => $table['CustomerReferenceNumber'],
                'Consignee' => $table['Consignee'],
                'ConsigneeAddress' => $table['ConsigneeAddress'],
                'ConsigneeContact' => $table['ConsigneeContact'],
                'ConsigneeEmail' => $table['ConsigneeEmail'],
                'ShipmentPieces' => $table['ShipmentPieces'],
                'ShipmentWeight' => $table['ShipmentWeight'],
                'Service' => $table['Service'],
                'Origin' => $table['Origin'],
                'Destination' => $table['Destination'],
                'Remarks' => $table['Remarks'],
                'Fragile' => $table['Fragile'],
                'InsuranceValue' => $table['InsuranceValue'],
                'DestinationCountry' => $table['DestinationCountry'],
                'ProductDetail' => $table['ProductDetail'],
                'CODAmount' => $table['CODAmount']
            ];
        }

        return $data;
    }

    public static function GetCNDetailsByReferenceNumberBlueEx(){
        set_time_limit(0);

        if (!defined('blueEx')) {
            define('blueEx', 'http://benefit.blue-ex.com/api/post.php');
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?><BenefitDocument><AccessRequest><DocumentType>6</DocumentType><ShipmentNumbers><Number>5005150693</Number></ShipmentNumbers></AccessRequest></BenefitDocument>';

        $credentials = json_decode(Helper::getStoreSpec(100009), true);
        $cred = $credentials['shippingdetails']['variables']['credentials']['bluex'];


        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, blueEx);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_USERPWD, $cred['username'] . ':' . $cred['password']);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, array('xml' => $xml));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type=application/soap+xml',
            'charset=utf-8'));

        $result = curl_exec($c);


        if($result!=false) {

            $sxe = new SimpleXMLElement($result);



            $encode = json_encode($sxe, true);

            if ($encode == '{}') {
                return 'Not Available';
            } else {

                $decode = json_decode($encode, true);

                $status = reset($decode);

                if(isset($status['statusrow'][0])){
                    dd($status['statusrow'][0]);
                }else {
                    dd($status['statusrow']['statusmessage']);
                }

                    return $status;


            }

       }
        return 'Not Available';
    }
    public static function GetCNDetailsByOrderIdKangroo($order_id){

        $params =[
            'order_id'=>$order_id,
            'client_id'=>'1',
            'api_pass' =>'sha3399'
        ];
        $curl   = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://stagging.kangaroo.pk/orderTrack",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $params,
            //CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $json_data = json_decode($response,true);

        return $json_data;

    }

    public static function est_delivery_time_leopard(){
          $star_end=array();
          $test=array();
          $sum=0;
          $difference=0;

          $logs = logs::select('order_id','status_timeline')->where('courier_name','leopard')->get();
          $data_count = logs::where('courier_name','=','leopard')->count();

        foreach($logs as $value) {

            $timeline =json_decode($value->status_timeline,true);
            $star_end['start'] =reset($timeline);
            $star_end['end']= end($timeline);
            array_push($test,$star_end);

            foreach($test as $key=>$data){

                $timestamp_start = strtotime($data['start']['Activity_Date']);
                $timestamp_end = strtotime($data['end']['Activity_Date']);
                $start_date= date("d",$timestamp_start);
                $end_date= date("d",$timestamp_end);
                $difference = $start_date - $end_date;
                $sum = $start_date + $difference;
            }
          $difference=0;

        }


        $average = $sum /$data_count;

        return $average;
    }


   public static function woocommerceOrderNoteCurlRequest($store_url,$order_note,$order_id,$consumer_key,$consumer_secret){


       $curl = curl_init();
        $url = $store_url."/wp-json/wc/v2/orders/".$order_id."/notes?note=".$order_note."&consumer_key=".$consumer_key."&consumer_secret=".$consumer_secret;

       curl_setopt_array($curl, array(
           CURLOPT_URL => $url,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => "",
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 30,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => "POST",
           CURLOPT_HTTPHEADER => array(

               "content-type: application/json",

           ),
       ));

       $response = curl_exec($curl);

       $err = curl_error($curl);
       return $response;

   }


    public static function GetOrderStatusLeopard($tracking_id)
    {


        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'http://new.leopardscod.com/webservice/trackBookedPacket/format/json/');  // Write here Test or Production Link
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, array(
            'api_key' => 'BC8A745A2B7DA612EFA7E26B96E8E829',
            'api_password' => 'A?(.>H5WL2MF9GU',
            'track_numbers' =>$tracking_id             // E.g. 'XXYYYYYYYY' OR 'XXYYYYYYYY,XXYYYYYYYY,XXYYYYYY' 10 Digits each number
        ));

        $buffer = curl_exec($curl_handle);
        dd($buffer);
        curl_close($curl_handle);
        $result = json_decode($buffer, true);
         dd($result);
        if($result['packet_list']!=null) {
             dd($result['packet_list'][0]['booked_packet_status']);
            // $table = end($result['packet_list']);
            // if($table['Tracking Detail']!=null) {

            //     $orderStatus = $table['Tracking Detail'];
            //     $finalStatus = reset($orderStatus);
            //      print_r ($finalStatus['Status']);
            //     return $finalStatus['Status'];
            // }else{
            //     return 'Not Available';
            // }
        }else{
            return 'Not Available';
        }


    }
    //test vb1
 public static function GetCNDetailsByReferenceNumberCallCourier($tracking_id){

    $curl   = curl_init();
    $url="http://cod.callcourier.com.pk/api/CallCourier/GetTackingHistory?cn=".$tracking_id;

     curl_setopt_array($curl, array(
         CURLOPT_URL => "http://cod.callcourier.com.pk/api/CallCourier/GetTackingHistory?cn=".$tracking_id,

         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => "",
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 30,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => "GET",
         //CURLOPT_POSTFIELDS => $params,
         //CURLOPT_HTTPHEADER => $headers,
     ));

     $response = curl_exec($curl);

     $err = curl_error($curl);
     curl_close($curl);
     $json_data = json_decode($response,true);
     return $json_data;

 }
    public static function GetTcsOrderStatus()
    {

        // define('tcs', "http://track.tcs.com.pk/trackingaccount/track.asmx");
        $xml = '<?xml version="1.0" encoding="utf-8"?>
                            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                              <soap:Body>
                               <DataSet_DeliveryDetails_CN xmlns="http://tempuri.org/">
                                <CN>77213217108</CN>
                               </DataSet_DeliveryDetails_CN>
                              </soap:Body>
                            </soap:Envelope>';
        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($xml),
        );

        $c = \curl_init();
        curl_setopt($c, CURLOPT_URL, "http://track.tcs.com.pk/trackingaccount/track.asmx");
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($c);
        $sxe = new SimpleXMLElement($result);
        $sxe->registerXPathNamespace('d', 'urn:schemas-microsoft-com:xml-diffgram-v1');
        $res = $sxe->xpath("//NewDataSet");

        dd($result);
        if($res!=null) {
            $json = json_encode($res);
            $array = json_decode($json, TRUE);
            $final_status = reset($array[0]['Table1']);

            return $final_status['STATUS'];
        }else{

            return null;
        }

    }
    public static function GetKangrooOrderStatus($order_tracking_id){

        $params =[
            'order_id'=>'21695',
            'client_id'=>'134',
            'api_pass' =>'1234567890'
        ];
        $curl   = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://kangaroo.pk/orderStatus",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $params,
            //CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        dd($response);
        $err = curl_error($curl);
        curl_close($curl);
        $json_data = json_decode($response,true);
        if($json_data['orderCurrentStatus']['order_status']!=null){
            dd($json_data['orderCurrentStatus']['order_status']);

            //return $json_data['orderCurrentStatus']['order_status'];
        }else {
            dd('sds');
            return null;
        }
    }

    public static function sendSmsOnOrderShipped($smsPayload){
        $url="http://api.technify.pk/smsmodule";
        $username="ahsans895@gmail.com";
        $password ="ahsan11";
        $payload = [
          'action'=>'sendSmsToCustomer',
          'timestamp'=>'2017-08-08',
          'dataPacket'=>[
              'store_id'=>$smsPayload['store_id'],
              'mobile_number'=>$smsPayload['telephone'],
              'sms_body'=> 'Thankyou for booking your order at'.' '. $smsPayload['store_name'].' '.'your '.$smsPayload['courier_company'].' '. 'tracking id is '.$smsPayload['tracking_id']
          ]
        ];
        $curl=Helper::curlRequestWithBasicAuth($url, $payload, $username, $password);
        return $curl;

    }

    // get store info using store id/email/lisence_key
    public static function getStoreInfo($id_email_or_license_key, $select = '*')
    {
        $store_info = Stores::select($select)->where('technify_store_id', $id_email_or_license_key)->orwhere('email', $id_email_or_license_key)->orwhere('uuid', $id_email_or_license_key)->first();

        if( $store_info ) {
            $response = ['response' => true, 'storeInfo' => $store_info];
        } else {
            $response = ['response' => false, 'errorMessage' => 'invalid/no data found'];
        }
        
        return $response; 
    }

    // curl request for all request 
    public static function curl($url, $request, $payload = [], $headers = [])
    {               
        if($headers){
            $headers = Helper::arrayToCurlHeader($headers);            
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => $request,
            CURLOPT_POSTFIELDS      => json_encode($payload),
            CURLOPT_HTTPHEADER      => $headers,
            )); 
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }  
    }

    // curl request for all request 
    public static function curlSentRequest($url, $request, $payload = [], $headers = [], $return = true)
    {               
        if($headers){
            $headers = Helper::arrayToCurlHeader($headers);            
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => $return,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => $request,
            CURLOPT_POSTFIELDS      => $payload,
            CURLOPT_HTTPHEADER      => $headers,
            )); 
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }  
    }

    // array convert to curl header
    public static function arrayToCurlHeader($header)
    {
        foreach ($header as $key => $value) {
            $headers[] = $key.':'.$value;
        }  
        return $headers;
    }

    // check vendor order exist
    public static function checkOrderExist($filter)
    {
        $check_row_exist = Shipping::where($filter)->first();
        if($check_row_exist){
            $response = ['response' => true, 'orderDetails' => $check_row_exist];
        } else {
            $response = ['response' => false];
        }
        return $response;
    }

    // create constant request
    public static function createCurlPayload($action, $datapacket)
    {
        return [
            'action'     => $action,
            'timestamp'  => Carbon::now()->toDateTimeString(),
            'dataPacket' => $datapacket
        ];
    }

    // split order object into db fileds
    public static function splitOrderObjectToFields($order_object)
    {
        $datapacket = $order_object['dataPacket'];
        $orderinfo = json_encode($order_object);
        $amount = (int) Helper::getOrderTotalAmount($datapacket['total']);
        $address = $datapacket['shipping_address']['address'];
        $city = $datapacket['shipping_address']['city'];
        $email = $datapacket['customer']['email'];
        $customer_number = $datapacket['customer']['telephone'];
        $payment_method = $datapacket['payment_method']['code'];

        return compact('amount', 'address', 'city', 'customer_number', 'payment_method', 'email', 'orderinfo');
    }

    // get vendor order status confirmation order status id
    public static function getShippingSettingsOrderStatus($store_id, $key = false)
    {        
        $get_order_status = ShippingSettings::select('order_status')->where(compact('store_id'))->first();
        if($get_order_status){
            if($key){

                $settings_status = json_decode($get_order_status->order_status, true);
                if( isset( $settings_status[$key] )) {
                    $response = ['response' => true, 'orderStatusID' => $settings_status[$key]];
                } else {
                    $response = ['response' => false];
                }
            } else {
                $response = ['response' => true, 'orderStatusID' => json_decode($get_order_status->order_status, true)];    
            }
        } else {
            $response = ['response' => false];
        }
        return $response;
    }

    public function FunctionName($value='')
    {
        return [
            'ship by kangaroo',
            'ship by tcs',
            'ship by leoaprd',
            'ship by bluex',
            'ship by callcourier',
            'ship by stallion',
            'ship by fedex'
        ];
    }

}

?>