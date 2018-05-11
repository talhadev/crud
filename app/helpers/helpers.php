<?php

/**
 * Created by PhpStorm.
 * User: Shehry
 * Date: 8/30/2017
 * Time: 9:10 AM
 */

// Models
use App\Models\Shipping;

function generateResponse($action, $statusCode, $response, $msg){
    return[
        'action'     => $action,
        'statusCode' => $statusCode,
        'response'   => $response,
        'timestamp'  => \Carbon\Carbon::now()->toDateTimeString(),
        'dataPacket' => [
            'ordersInfo' => $msg
    ],
    ];
}
function curlRequest($url, $headers, $params){
    $curl                       = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL             => $url,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_ENCODING        => "",
        CURLOPT_MAXREDIRS       => 10,
        CURLOPT_TIMEOUT         => 30,
        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST   => "POST",
        CURLOPT_POSTFIELDS      => $params,
        CURLOPT_HTTPHEADER      => $headers,
    ));
    $response                  = curl_exec($curl);
    $err                        = curl_error($curl);
    curl_close($curl);

    return $response;
}
function get_header_array($headers = [])
{
    $header = [];
    foreach ($headers as $header_key => $header_value)
    {
        $header[] = $header_key . ":" . $header_value;
    }

    return $header;
}

function orderStatus($store_id, $order_id)
{
    $filter = ['order_id' => $order_id, 'store_id' => $store_id];
    $get_success_order = Shipping::select('order_id', 'status')->where($filter)->first();
    if( $get_success_order && $get_success_order->status == '1' ) {
        $response = ['status' => '1', 'order_id' => $get_success_order->order_id];
    } else if( $get_success_order && $get_success_order->status == '2' ) {
        $response = ['status' => '2', 'order_id' => $get_success_order->order_id];
    } else {
        $response = ['status' => '0'];
    }

    return $response;
}

function validateUpdateOrders($action,$store_id, $order_id, $city, $address, $phone){
    $validator = Validator::make(
        array(
            'store_id' => $store_id,
            'order_id' => $order_id,
            'address' => $address,
            'city' => $city,
            'phone' => $phone,),
        array(
            'store_id' => 'required|integer',
            'order_id' => 'required|integer',)
    );

    return $validator;
}

function curlRequestUpdateAndProceed($returntransfer, $url, $useragent, $payload){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => $returntransfer,
        CURLOPT_URL            => $url,
        CURLOPT_USERAGENT      => $useragent,
        CURLOPT_POST           => 'POST',
        CURLOPT_POSTFIELDS     => array(
            'payload'          => json_encode($payload)
        )
    ));
    $resp = json_decode(curl_exec($curl), true);
    curl_close($curl);
    return $resp;
}

function GetCNDetailsByReferenceNumberTCS($order_id){

    $params = ['m2.cod', 'police123'];
    $client = new \nusoap_client(Config::get('urls.courier_urls.tcs'), true);
    $result = $client->call("GetCNDetailsByReferenceNumber", [
         'userName' => $params[0],
         'password' => $params[1],
         'customerReferenceNo' =>$order_id,
    ]);
     foreach ((array)$result as $res) {
         $ConsignmentNumber = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['ConsignmentNumber'];
         $CustomerReferenceNumber = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['CustomerReferenceNumber'];
         $Consignee = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['Consignee'];
         $ConsigneeAddress = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['ConsigneeAddress'];
         $ConsigneeContact = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['ConsigneeContact'];
         $ConsigneeEmail = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['ConsigneeEmail'];
         $ShipmentPieces = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['ShipmentPieces'];
         $ShipmentWeight = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['ShipmentWeight'];
         $Service = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['Service'];
         $Origin = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['Origin'];
         $Destination = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['Destination'];
         $Remarks = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['Remarks'];
         $Fragile = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['Fragile'];
         $InsuranceValue = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['InsuranceValue'];
         $DestinationCountry = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['DestinationCountry'];
         $ProductDetail = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['ProductDetail'];
         $CODAmount = $result['GetCNDetailsByReferenceNumberResult']['diffgram']['NewDataSet']['Table']['CODAmount'];
     }
     $data = [
         'CN' => $ConsignmentNumber,
         'CustomerRef' => $CustomerReferenceNumber,
         'Consignee' => $Consignee,
         'ConsigneeAddress' => $ConsigneeAddress,
         'ConsigneeContact' => $ConsigneeContact,
         'ConsigneeEmail' => $ConsigneeEmail,
         'ShipmentPieces' => $ShipmentPieces,
         'ShipmentWeight' => $ShipmentWeight,
         'Service' => $Service,
         'Origin' => $Origin,
         'Destination' => $Destination,
         'Remarks' => $Remarks,
         'Fragile' => $Fragile,
         'InsuranceValue' => $InsuranceValue,
         'DestinationCountry' => $DestinationCountry,
         'ProductDetail' => $ProductDetail,
         'CODAmount' => $CODAmount,
         'date' => $result
     ];
     return $data;
}

function validateStoreIdAndOrderIds($store_id, $order_id){
    $validator = Validator::make(
        array(
            'store_id' => $store_id,
            'order_id' => $order_id),
        array(
            'store_id' => 'required|integer',
            'order_id' =>'required|integer'
        ));
    return $validator;
}