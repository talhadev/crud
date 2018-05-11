<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipping;
use Artisaninweb\SoapWrapper\SoapWrapper;
use Config;
use App\Models\Stores;
use App\Models\Orderfailure;
use App\Jobs\SendOrderSuccess;
use App\Jobs\SendOrderFailure;
use Mail;
use Helper;

class ShippingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {                   
        return view('shipping.index');
    }

    // send pakages to couriers
    public function shipped(Request $request) {              
        
        ini_set('max_execution_time', 300); // 60 * 5 (5mins)

        $timestamp = Helper::datetime();
        
        $order_info = json_decode($request->payload, true);   
        
        if( !empty($order_info) ) {                  

          $store_id = $order_info['orderinfo']['store_id'];          
          
          $city = strtolower($order_info['shipping_address']['city']);
          $address = strtolower($order_info['shipping_address']['address']);

          $data = Helper::getcity($address, $city, $store_id);                  
          
          if( !empty($data) && $data !== '' ) {      
            
            $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);
            
            $order_info['orderinfo']['store_name'] = $get_store_spec['store_info']['name'];
            $order_info['orderinfo']['store_url']  = $get_store_spec['store_info']['url'];

            $city      = strtolower($data['city']);
            $address   = strtolower($order_info['shipping_address']['address']);
            $telephone = strtolower($order_info['customer']['telephone']);
            
            if(isset($get_store_spec['shippingdetails'])) {
                $companies = $get_store_spec['shippingdetails']['variables']['credentials'];
                
                foreach ($companies as $key => $value) {
                    if( $data['courier_company'] == $key ) {
                        $credentials = $value;
                        $origincity  = $get_store_spec['shippingdetails']['variables']['origincity'];
                        break;
                    }
                }                
            }                        
            
            if($timestamp && $credentials && $origincity && $order_info) {
              $shipping_json = [
                'action'       => 'shippingdetails',
                'timestamp'    => $timestamp,
                'ifCityIsNull' => $data['city'],
                'credentials'  => $credentials,
                'origincity'   => $origincity,
                'datapacket'   => $order_info
              ];
            }
            
            $curl = curl_init();                        
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,                
                CURLOPT_URL => Config::get('urls.curl_urls.curl_api_shipped').$data['courier_company'],
                CURLOPT_USERAGENT => 'Technify Order Parsal Request',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => array(
                   'payload' => json_encode($shipping_json)
                )
            ));            

            $curl_response = json_decode(curl_exec($curl), true);       // dd($curl_response);
            curl_close($curl);      
            
            if( $curl_response['response'] == true ) {

                $tracking_id = $curl_response['tracking_id'];  
                $order_id    = $curl_response['order_id'];
                $store_url   = $curl_response['store_url'];
                $courier_company = $curl_response['courier_company'];
                $data = ['order_id' => $order_id, 'tracking_id' => $tracking_id, 'courier_company' => $courier_company, 'courier_domain' => $courier_company];
                
                $support_email = Stores::select('email', 'support_email')->where('id', $store_id)->first();
                
                if($support_email) {
                    $emails = str_replace(" ", "", explode(',', $support_email->support_email));   // send emails for order success                           
                    foreach ($emails as $email) {   
                        dispatch(new SendOrderSuccess($data, $email));                   
                    }
                }                
                $datapacket = ['shipped_to' => $courier_company, 'tracking_id' => $tracking_id, 'order_id' => $order_id, 'address' => $address, 'city' => $city, 'telephone' => $telephone];
                $response = ['action' => 'updateOrder', 'response' => true, 'datapacket' => $datapacket];                
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,                
                    CURLOPT_URL => $store_url.'/index.php?route=module/technify_shipping/updateFailureOrder',
                    CURLOPT_USERAGENT => 'Technify Shipping Update Order',
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => array
                        ('datapacket' => json_encode($datapacket))
                )); 

                $updateorder_resp = curl_exec($curl);   
                $error = curl_error($curl);   
                curl_close($curl);                        

            } else {
                $shipping_json = [
                  'action'          => 'orderfailure',
                  'timestamp'       => $timestamp,
                  'failure_address' => $order_info['shipping_address']['address'],
                  'failure_city'    => $order_info['shipping_address']['city'],
                  'datapacket'      => $order_info
                ];  

                $order_id        = $shipping_json['datapacket']['orderinfo']['order_id'];
                $store_id        = $shipping_json['datapacket']['orderinfo']['store_id'];
                $telephone       = $shipping_json['datapacket']['customer']['telephone'];
                $email           = $shipping_json['datapacket']['customer']['email'];
                $failure_address = $shipping_json['failure_address'];
                $failure_city    = $shipping_json['failure_city'];

                $this->processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json);
                $response = ['response' => false, 'errorMessage' => $curl_response['errorMessage']];
            }            

          } else {
              $shipping_json = [
                'action'          => 'orderfailure',
                'timestamp'       => $timestamp,
                'failure_address' => $order_info['shipping_address']['address'],
                'failure_city'    => $order_info['shipping_address']['city'],
                'datapacket'      => $order_info
              ];  
              $order_id        = $shipping_json['datapacket']['orderinfo']['order_id'];
              $store_id        = $shipping_json['datapacket']['orderinfo']['store_id'];
              $telephone       = $shipping_json['datapacket']['customer']['telephone'];
              $email0          = $shipping_json['datapacket']['customer']['email'];
              $failure_address = $shipping_json['failure_address'];
              $failure_city    = $shipping_json['failure_city'];

              $response = ['response' => false, 'errorMessage' => $this->processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json)];              
          }

        } else {            
            $response = ['response' => false, 'errorMessage' => 'order does not exist'];
        }          

        return $response;
    }

    // API for kangaroo 
    public function kangaroo(Request $request) {      

      $file = 'kangaroo.json';           
      file_put_contents($file, $request->payload, true);   

      $json = json_decode($request->payload, true); 
      
      $order_total_price = Helper::getOrderTotalAmount($json['datapacket']['total']);

      $address = $json['datapacket']['shipping_address']['address'].', '.$json['datapacket']['shipping_address']['address_2'];
      $order_id = $json['datapacket']['orderinfo']['order_id'];      
      $productDetails = $json['datapacket']['cart'];      
      $products = Helper::productDetails($productDetails);      
      
      $url         = Config::get('urls.courier_urls.kangaroo');        
      $clientid    = $json['credentials']['clientid'];
      $pass        = $json['credentials']['password'];
      $cname       = $json['datapacket']['customer']['firstname'].' '.$json['datapacket']['customer']['lastname'];
      $caddress    = $address;
      $cnumber     = $json['datapacket']['customer']['telephone'];
      $amount      = $order_total_price;
      $invoice     = 'invoive-'.rand(10000, 99999);        
      $pname       = 'Products: '.$products['name'];      // optional
      $pcode       = 'QTY: '.$products['qty'];      // optional
      $city        = $json['origincity'];
      $orderType   = $json['datapacket']['payment_method'][0]["code"];      

      $curl_params = array(
          "clientid" => $clientid,
          "pass" => $pass,
          "cname" => $cname,
          "caddress" => $caddress,
          "cnumber" => $cnumber,
          "amount" => $amount,
          "invoice" => $invoice,
          "pname" => $pname,
          "pcode" => $pcode,
          "city" => $city,
          "orderType" => $orderType,
      );

      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_params);
      $curl_response = curl_exec($curl); 
      curl_close($curl);

      $response = json_decode($curl_response, true);  
      
      if(isset($response['order id'])){
          $order_tracking_id = $response['order id'];
          $order_id = $json['datapacket']['orderinfo']['order_id'];          
          $store_id = $json['datapacket']['orderinfo']['store_id'];     
          $store_url = $json['datapacket']['orderinfo']['store_url'];               
          $orderinfo = $request->payload;

          $check_order_exist = Shipping::where('order_id', $order_id)->where('status', '2')->first();

          if($check_order_exist && $check_order_exist->status == '2') {
              $update_data = ['order_tracking_id' => $order_tracking_id, 'status' => '1'];
              $check_order_exist->update($update_data);
          } else {
              $data = ['order_id' => $order_id, 'store_id' => $store_id, 'orderinfo' => $orderinfo, 'courier_name' =>'kangaroo', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];          
              Shipping::create($data);
          }
                  
          $res = ['response' => true, 'order_id' => $order_id, 'store_url' => $store_url, 'tracking_id' => $order_tracking_id, 'courier_company' => 'kangaroo'];
      } else {
          $res = ['response' => false];
      }     
      return response()->json($res); 
    }

    // API for TCS 
    public function tcs(Request $request) {         
      // dd($this->datetime());               
      file_put_contents('tcs.json', $request->payload, true);   
      
      $json = json_decode($request->payload, true); 
      
      $order_total_price = Helper::getOrderTotalAmount($json['datapacket']['total']);
      $address = $json['datapacket']['shipping_address']['address'].', '.$json['datapacket']['shipping_address']['address_2'];
      $city = $json['ifCityIsNull'];
      $order_id = $json['datapacket']['orderinfo']['order_id'];      
      $productDetails = $json['datapacket']['cart'];      
      
      $products = Helper::productDetails($productDetails);      

      $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);
      $result = $client->call("InsertData", [
        'userName'             => $json['credentials']['username'], 
        'password'             => $json['credentials']['password'], 
        'costCenterCode'       => $json['credentials']['costCenterCode'], 
        'consigneeName'        => $json['datapacket']['customer']['firstname'].' '.$json['datapacket']['customer']['lastname'],
        'consigneeAddress'     => $address,
        'consigneeMobNo'       => $json['datapacket']['customer']['telephone'],
        'consigneeEmail'       => $json['datapacket']['customer']['email'],
        'originCityName'       => $json['origincity'],        
        'destinationCityName'  => $city,
        'pieces'               => 1,
        'weight'               => ($json['datapacket']['weight'] && $json['datapacket']['weight'] >= 0.5) ? $json['datapacket']['weight'] : '0.5',
        'codAmount'            => $order_total_price,
        'custRefNo'            => $order_id,
        'productDetails'       => 'Products: '.$products['name'].'-----QTY: '.$products['qty'],
        'fragile'              => 'No',
        'services'             => 'O',
        'remarks'              => 'QTY: '.$products['qty'],
        'insuranceValue'       => '0',
      ]);   
            
      if(isset($result['InsertDataResult']) && $result['InsertDataResult'] !== 'Invalid City.') {
          $order_tracking_id = $result['InsertDataResult'];
          $order_id  = $json['datapacket']['orderinfo']['order_id'];          
          $store_id  = $json['datapacket']['orderinfo']['store_id'];          
          $store_url = $json['datapacket']['orderinfo']['store_url'];          
          $orderinfo = $request->payload;
          
          $check_order_exist = Shipping::where('order_id', $order_id)->first();

          if($check_order_exist) {
              $update_data = ['order_tracking_id' => $order_tracking_id, 'status' => '1'];
              $check_order_exist ->update($update_data);
          } else {
              $data = ['order_id' => $order_id, 'store_id' => $store_id, 'orderinfo' => $orderinfo, 'courier_name' =>'tcs', 'order_tracking_id' => $order_tracking_id, 'status' => '1'];          
              Shipping::create($data);
          }
          
          $response = ['response' => true, 'order_id' => $order_id, 'store_url' => $store_url, 'tracking_id' => $order_tracking_id, 'courier_company' => 'tcs'];
      } else {
          $response = ['response' => false];
      }     
      return response()->json($response); 
    }
    
    // LEAOPARD 
    public function leopard(Request $request) {   

      $file = 'leopard.json';      
      file_put_contents($file, $request->payload, true);   
      
      $leoaprd    = json_decode($request->payload, true); 
      $store_id   = $leoaprd['datapacket']['orderinfo']['store_id'];
      $store_spec = json_decode(Helper::getStoreSpec($store_id), true);
      $url        = Config::get('urls.courier_urls.leopard');
      $order_id   = $leoaprd['datapacket']['orderinfo']['order_id'];  
      $address    = $leoaprd['datapacket']['shipping_address']['address'].', '.$leoaprd['datapacket']['shipping_address']['address_2'];
      $order_total_price = Helper::getOrderTotalAmount($leoaprd['datapacket']['total']);      
      $city       = $leoaprd['ifCityIsNull'];               
      $productDetails = $leoaprd['datapacket']['cart'];                 
      $products   = Helper::productDetails($productDetails);
      $weight     = (($leoaprd['datapacket']['weight'] && $leoaprd['datapacket']['weight'] >= 0.5) ? $leoaprd['datapacket']['weight'] : '0.5') * 1000;
      $origin_city = $store_spec['shippingdetails']['variables']['origincity'];
      $origin_city = $this->getLeopardCityID($origin_city);
      $destination_city = $this->getLeopardCityID($city);
      
      $payload = array(
          'api_key'                      => $leoaprd['credentials']['api_key'],
          'api_password'                 => $leoaprd['credentials']['api_password'],
          'booked_packet_weight'         => $weight,  // weight in grams
          'booked_packet_vol_weight_w'   => '',       
          'booked_packet_vol_weight_h'   => '',       // OPTIONAL
          'booked_packet_vol_weight_l'   => '',
          'booked_packet_no_piece'       => $products['qty'],
          'booked_packet_collect_amount' => $order_total_price,
          'booked_packet_order_id'       => $leoaprd['datapacket']['orderinfo']['order_id'],         // optinal
          'origin_city'                  => $origin_city,    // $json['origincity']
          'destination_city'             => $destination_city,     
          'shipment_name_eng'            => $leoaprd['datapacket']['orderinfo']['store_name'],
          'shipment_email'               => $store_spec['store_info']['email'],
          'shipment_phone'               => $store_spec['store_info']['phone'],
          'shipment_address'             => $store_spec['store_info']['address'],
          'consignment_name_eng'         => $leoaprd['datapacket']['customer']['firstname'].' '.$leoaprd['datapacket']['customer']['lastname'],
          'consignment_email'            => $leoaprd['datapacket']['customer']['email'],
          'consignment_phone'            => $leoaprd['datapacket']['customer']['telephone'],
          'consignment_phone_two'        => '',      //OPTIONAL
          'consignment_phone_three'      => '',
          'consignment_address'          => $address,
          'special_instructions'         => 'Instructions'
      );          
      
      $response = Helper::curlRequest($url, $payload);
      $buffer = json_decode($response, true);
      
      if($buffer['status'] == 1){
          $traking_no = $buffer['track_number'];
          $order_id   = $leoaprd['datapacket']['orderinfo']['order_id'];          
          $store_id   = $leoaprd['datapacket']['orderinfo']['store_id'];  
          $store_url  = $leoaprd['datapacket']['orderinfo']['store_url'];           
          $orderinfo  = $request->payload;

          $check_order_exist = Shipping::where('order_id', $order_id)->first();

          if($check_order_exist) {
              $update_data = ['order_tracking_id' => $traking_no, 'status' => '1'];
              $check_order_exist ->update($update_data);
          } else {
              $data = ['order_id' => $order_id, 'store_id' => $store_id, 'orderinfo' => $orderinfo, 'courier_name' =>'leopard', 'order_tracking_id' => $traking_no, 'status' => '1'];          
              Shipping::create($data);
          }
          
          $res = ['response' => true, 'order_id' => $order_id, 'store_url' => $store_url, 'tracking_id' => $traking_no, 'courier_company' => 'leopard'];
                    
      } else {
          $res = ['response' => false, 'errorMessage' => $buffer['error']];
      }     
      return response()->json($res); 
    }

    public function callCourier(Request $request) {
                
        file_put_contents('callCourier.json', $request->payload, true); 

        $call_courier = json_decode($request->payload, true);
        
        $store_spec = json_decode(Helper::getStoreSpec($call_courier['datapacket']['orderinfo']['store_id']), true);        

        $url = Config::get('urls.courier_urls.call_courier');
        $loginId = $call_courier['credentials']['loginId'];
        $customer_name = $call_courier['datapacket']['customer']['firstname'].' '.$call_courier['datapacket']['customer']['lastname'];
        $telephone = $call_courier['datapacket']['customer']['telephone'];
        $order_id = $call_courier['datapacket']['orderinfo']['order_id'];  
        $address = preg_replace("/[\s_]/", "-", $call_courier['datapacket']['shipping_address']['address'].', '.$call_courier['datapacket']['shipping_address']['address_2']);
        $order_total_price = Helper::getOrderTotalAmount($call_courier['datapacket']['total']);      
        $city = $call_courier['datapacket']['shipping_address']['city'];                
        $productDetails = $call_courier['datapacket']['cart'];                 
        $products = Helper::productDetails($productDetails);
        $qty = $products['qty'];        
        $weight = ($call_courier['datapacket']['weight'] && $call_courier['datapacket']['weight'] >= 0.5) ? $call_courier['datapacket']['weight'] : '0.5';
        $origin_city = $store_spec['shippingdetails']['variables']['origincity'];
        $desc = $products['name'];
        $destination_city = 18;
        $shipper_name  = $store_spec['store_info']['name'];
        $shipper_cell  = $store_spec['store_info']['phone'];
        $shipper_addr  = preg_replace("/[\s#]/", "-", $store_spec['store_info']['address']);
        $shipper_email = $store_spec['store_info']['email'];
               
        $url = $url."?loginId=$loginId&ConsigneeName=$customer_name&ConsigneeRefNo=$order_id&ConsigneeCellNo=$telephone&Address=$address&Origin=$origin_city&DestCityId=$destination_city&ServiceTypeId=7&Pcs=$qty&Weight=$weight&Description=$desc&SelOrigin=Domestic&CodAmount=$order_total_price&SpecialHandling=false&MyBoxId=1%20My%20Box%20ID&Holiday=false&remarks=Remarks&ShipperName=$shipper_name&ShipperCellNo=$shipper_cell&ShipperArea=1&ShipperCity=1&ShipperAddress=$shipper_addr&ShipperLandLineNo=$shipper_cell&ShipperEmail=$shipper_email";
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL   => $url,
            CURLOPT_POST  => 'GET',
        ));
        $resp = json_decode(curl_exec($curl), true);
        curl_close($curl);        
        return $resp;

    }

    // BLUE EX 
    public function bluex(Request $request) {
      
      $file = 'bluex.json';      
      file_put_contents($file, $request->payload, true);   

      $json = json_decode($request->payload, true); 
      $store_id = $json['datapacket']['orderinfo']['store_id'];
      $order_total_price = Helper::getOrderTotalAmount($json['datapacket']['total']);
      $address = $json['datapacket']['shipping_address']['address'].', '.$json['datapacket']['shipping_address']['address_2'];
      $city = $json['ifCityIsNull'];
      $order_id = $json['datapacket']['orderinfo']['order_id'];      
      $productDetails = $json['datapacket']['cart'];            
      $products = Helper::productDetails($productDetails); 
      $weight = ($json['datapacket']['weight'] && $json['datapacket']['weight'] >= 0.5) ? $json['datapacket']['weight'] : 0.5;
      $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);     
      
      set_time_limit(0);
      define('blueEx', Config::get('urls.courier_urls.blueex'));
      $xml ="<?xml version='1.0' encoding='utf-8'?>
      <BenefitDocument>
        <AccessRequest>
        <DocumentType>1</DocumentType>
        <TestTransaction></TestTransaction>
            <ShipmentDetail>
                <ShipperName>'".$get_store_spec['store_info']['name']."'</ShipperName>
                <ShipperAddress>'". $get_store_spec['store_info']['address'] ."'</ShipperAddress>
                <ShipperContact>". $get_store_spec['store_info']['phone'] ."</ShipperContact>
                <ShipperEmail>". $get_store_spec['store_info']['email'] ."</ShipperEmail>
                <ConsigneeName>". $json['datapacket']['customer']['firstname']." ".$json['datapacket']['customer']['lastname'] ."</ConsigneeName>
                <ConsigneeAddress>". $address ."</ConsigneeAddress>
                <ConsigneeContact>". $json['datapacket']['customer']['telephone'] ."</ConsigneeContact>
                <ConsigneeEmail>". $json['datapacket']['customer']['email'] ."</ConsigneeEmail>
                <CollectionRequired>Y</CollectionRequired>
                <ProductDetail>Products: ".$products['name']."-----QTY: ".$products['qty'] ."</ProductDetail>
                <ProductValue>". $order_total_price ."</ProductValue>
                <OriginCity>". $json['origincity'] ."</OriginCity>
                <DestinationCountry>PK</DestinationCountry>
                <DestinationCity>LHE</DestinationCity>
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
      curl_setopt($curl, CURLOPT_URL, blueEx );
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
      curl_setopt($curl, CURLOPT_USERPWD, 'companion:123456');
      curl_setopt($curl, CURLOPT_POST, 1 );
      curl_setopt($curl, CURLOPT_POSTFIELDS, array('xml'=>$xml) );
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type=application/soap+xml', 'charset=utf-8'));
      $result = curl_exec ($curl);
      dd($result);

    }

    // FEDEX
    public function fedex() { 
        // dd($this->datetime());
        // Whoever introduced xml to shipping companies should be flogged
        $xml  = '<?xml version="1.0"?>';
        $xml .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://fedex.com/ws/rate/v10">';
        $xml .= ' <SOAP-ENV:Body>';
        $xml .= '   <ns1:RateRequest>';
        $xml .= '     <ns1:WebAuthenticationDetail>';
        $xml .= '       <ns1:UserCredential>';
        $xml .= '         <ns1:Key>mVL6VAcj7vY5HsKt</ns1:Key>';
        $xml .= '         <ns1:Password>8fpbgN60OusL2vUy3Lh7USf2d</ns1:Password>';
        $xml .= '       </ns1:UserCredential>';
        $xml .= '     </ns1:WebAuthenticationDetail>';
        $xml .= '     <ns1:ClientDetail>';
        $xml .= '       <ns1:AccountNumber>510087780</ns1:AccountNumber>';
        $xml .= '       <ns1:MeterNumber>118824829</ns1:MeterNumber>';
        $xml .= '     </ns1:ClientDetail>';
        $xml .= '     <ns1:Version>';
        $xml .= '       <ns1:ServiceId>crs</ns1:ServiceId>';
        $xml .= '       <ns1:Major>10</ns1:Major>';
        $xml .= '       <ns1:Intermediate>0</ns1:Intermediate>';
        $xml .= '       <ns1:Minor>0</ns1:Minor>';
        $xml .= '     </ns1:Version>';
        $xml .= '     <ns1:ReturnTransitAndCommit>true</ns1:ReturnTransitAndCommit>';
        $xml .= '     <ns1:RequestedShipment>';
        $xml .= '       <ns1:ShipTimestamp>' . Helper::datetime() . '</ns1:ShipTimestamp>';
        $xml .= '       <ns1:DropoffType>REGULAR_PICKUP</ns1:DropoffType>';
        $xml .= '       <ns1:PackagingType>FEDEX_BOX</ns1:PackagingType>';
        $xml .= '       <ns1:Shipper>';
        $xml .= '         <ns1:Contact>';
        $xml .= '           <ns1:PersonName>Test</ns1:PersonName>';
        $xml .= '           <ns1:CompanyName>Test Company</ns1:CompanyName>';
        $xml .= '           <ns1:PhoneNumber>03457654321</ns1:PhoneNumber>';
        $xml .= '         </ns1:Contact>';
        $xml .= '         <ns1:Address>';
        $xml .= '           <ns1:StateOrProvinceCode>TN</ns1:StateOrProvinceCode>';
        $xml .= '           <ns1:PostalCode>38117</ns1:PostalCode>';
        $xml .= '           <ns1:CountryCode>US</ns1:CountryCode>';
        $xml .= '         </ns1:Address>';
        $xml .= '       </ns1:Shipper>';
        $xml .= '       <ns1:Recipient>';
        $xml .= '         <ns1:Contact>';
        $xml .= '           <ns1:PersonName>Ahsan Sheikh</ns1:PersonName>';
        $xml .= '           <ns1:CompanyName>Technify</ns1:CompanyName>';
        $xml .= '           <ns1:PhoneNumber>03467654321</ns1:PhoneNumber>';
        $xml .= '         </ns1:Contact>';
        $xml .= '         <ns1:Address>';
        $xml .= '           <ns1:StreetLines>60 Simcoe St, Toronto, ON M4B 1B3, Canada</ns1:StreetLines>';
        $xml .= '           <ns1:City>Toronto</ns1:City>';
        $xml .= '           <ns1:StateOrProvinceCode>ON</ns1:StateOrProvinceCode>';
        $xml .= '           <ns1:PostalCode>M4B 1B3</ns1:PostalCode>';
        $xml .= '           <ns1:CountryCode>CA</ns1:CountryCode>';
        $xml .= '           <ns1:Residential>true</ns1:Residential>';
        $xml .= '         </ns1:Address>';
        $xml .= '       </ns1:Recipient>';
        $xml .= '       <ns1:ShippingChargesPayment>';
        $xml .= '         <ns1:PaymentType>SENDER</ns1:PaymentType>';
        $xml .= '         <ns1:Payor>';
        $xml .= '           <ns1:AccountNumber>510087780</ns1:AccountNumber>';
        $xml .= '           <ns1:CountryCode>US</ns1:CountryCode>';
        $xml .= '         </ns1:Payor>';
        $xml .= '       </ns1:ShippingChargesPayment>';
        $xml .= '       <ns1:RateRequestTypes>LIST</ns1:RateRequestTypes>';
        $xml .= '       <ns1:PackageCount>1</ns1:PackageCount>';
        $xml .= '       <ns1:RequestedPackageLineItems>';
        $xml .= '         <ns1:SequenceNumber>1</ns1:SequenceNumber>';
        $xml .= '         <ns1:GroupPackageCount>1</ns1:GroupPackageCount>';
        $xml .= '         <ns1:Weight>';
        $xml .= '           <ns1:Units>LB</ns1:Units>';
        $xml .= '           <ns1:Value>400</ns1:Value>';
        $xml .= '         </ns1:Weight>';
        $xml .= '         <ns1:Dimensions>';
        $xml .= '           <ns1:Length>10</ns1:Length>';
        $xml .= '           <ns1:Width>10</ns1:Width>';
        $xml .= '           <ns1:Height>10</ns1:Height>';
        $xml .= '           <ns1:Units>IN</ns1:Units>';
        $xml .= '         </ns1:Dimensions>';
        $xml .= '       </ns1:RequestedPackageLineItems>';
        $xml .= '     </ns1:RequestedShipment>';
        $xml .= '   </ns1:RateRequest>';
        $xml .= ' </SOAP-ENV:Body>';
        $xml .= '</SOAP-ENV:Envelope>';

        $url = 'https://gatewaybeta.fedex.com/web-services/';
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        
        curl_close($curl);
        dd($response);
        /*$dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXml($response);*/
  
    }

    // process of order failure
    public function processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json)
    { 
        $support_email = Stores::select('email', 'support_email')->where('id', $store_id)->first();              
        if($support_email) {
            $emails = str_replace(" ", "", explode(',', $support_email->support_email));   // send emails for order failure
            $check_order_exist = Orderfailure::where('order_id', $order_id)->first();
            if(!$check_order_exist) {

                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'failure_address' => $failure_address, 'failure_city' => $failure_city, 'telephone' => $telephone, 'email' => $email, 'status' => '0', 'orderinfo' => json_encode($shipping_json)];
            
                Orderfailure::create($data);

            } else {                      

                $data = ['order_id' => $order_id, 'store_id' => $store_id, 'failure_address' => $failure_address, 'failure_city' => $failure_city, 'telephone' => $telephone, 'email' => $email, 'status' => '0', 'orderinfo' => json_encode($shipping_json)];
                $update_order_failure = Orderfailure::where('order_id', $order_id)->first();                                      
                $update_order_failure->update($data);

            }                   

            foreach ($emails as $email) {
                $data = ['order_id' => $order_id, 'app_url' => Config::get('urls.app_urls.app_url')];
                dispatch(new SendOrderFailure($data, $email));                    
            }

            $response = 'Some thing wrong your order please check your email or visit <a href="'.Config::get('urls.navigation_urls.order_failure').'">Order failure</a>';
        } else {
            $response = 'Store not authorize for Technify';
        }

        return $response;
    }

    // get city id for leopard 
    public function getLeopardCityID($city) {      
      
        $city_id = 'self';        
        $leopard_city_list = json_decode(file_get_contents('cities/leopard_city_list.json'), true);
        
        foreach ($leopard_city_list['city_list'] as $key => $value) {              
            if($value['name'] == ucfirst($city)) {
                $city_id = $value['id'];
                break;
            }             
        }

        return $city_id;
    }

    // cancel shimmpents
    public function cancelShipment(Request $request)
    {        
        $data = json_decode($request->data, true);      

        $store_id = $data['store_id'];  
        $city = Helper::getcity($data['address'], $data['city'], $store_id);             

        if( isset($city) && $city ) {
                    
            $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);

            if(isset($get_store_spec['shippingdetails'])) {
                $companies = $get_store_spec['shippingdetails']['variables']['credentials'];

                foreach ($companies as $key => $value) {  
                    if( $city['courier_company'] == $key ) {    
                        $credentials = $value;
                        break;
                    }
                }
            }                    
            
            $order_credentials = ['order_id' => $data['order_id'], 'store_id' => $store_id, 'credentials' => $credentials];

            $curl = curl_init();                        
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,                
                CURLOPT_URL => Config::get('urls.curl_urls.curl_api_shipped_cancel').$city['courier_company'],
                CURLOPT_USERAGENT => 'Technify cancel shipment',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => array(
                   'payload' => json_encode($order_credentials)
                )
            ));            
            $response = curl_exec($curl);  
            curl_close($curl);    
        }
    }

    // cancel shipment for TCS
    public function cancelshipmenttcs(Request $request) {        
        
        $data = json_decode($request->payload, true);  
        $order_id = $data['order_id'];
        $store_id = $data['store_id'];
        $filters = ['order_id' => $order_id, 'store_id' => $store_id];
        $traking_data = Shipping::where($filters)->first();        
        
        if( $traking_data && isset($traking_data) ) {
            $traking_no = $traking_data->order_tracking_id;            
        }
        
        if( $data && isset($data) && $traking_no ){
            $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);
            $result = $client->call("CancelShipment", [
              'userName'             => $data['credentials']['username'], 
              'password'             => $data['credentials']['password'], 
              'consigneeNumber'      => $traking_no,
            ]);
        }
        if( $result['CancelShipmentResult'] == true ) {
            $update_data = ['status' => 2];
            $traking_data->update($update_data);            
            return 1;
        } else {
            return 0;
        }        
    }

    public function cancelShipmentKangaroo(Request $request)
    {                     
        $data = json_decode($request->payload, true);  
        $order_id = $data['order_id'];
        $store_id = $data['store_id'];
        $filters = ['order_id' => $order_id, 'store_id' => $store_id];
        $traking_data = Shipping::where($filters)->first();

        if( $traking_data && isset($traking_data) ) {
            $traking_no = $traking_data->order_tracking_id;            
        }
        
        if( $data && isset($data) && $traking_no ) {
            $url = Config::get('urls.courier_urls.kangaroo_cacnel');
            
            $params = array(
              'clientid' => $data['credentials']['clientid'],                              
              'pass'     => $data['credentials']['password'],                         
              'orderid'  => $traking_no,
              'orderidd' => "Order ID: #".$order_id
            );

            $response = json_decode(Helper::curlRequest($url, $params), true);
            
        }        

        if( $response && $response['orderresponse'] == true ) {
            $update_data = ['status' => '2'];
            $traking_data->update($update_data);            
            return 1;
        } else {
            return 0;
        }        
    }

    // track TCS order by tracking no
    public function trackTcsOrder(Request $request)
    {   
        $json = json_decode($request->payload, true);         
        $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);        
        $result = $client->call("GetCNDetailsByReferenceNumber", [
          'userName'             => $json['credentials']['username'], 
          'password'             => $json['credentials']['password'], 
          'customerReferenceNo'  => $json['customerReferenceNo'] 
        ]);   

        dd($result);
    }
}
