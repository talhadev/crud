public function shipped($payload)
{



ini_set('max_execution_time', 300); // 60 * 5 (5mins)
$timestamp  = $payload['timestamp'];


$order_info = $payload['dataPacket'];

$action = $payload['action'];

$license_key = $payload['license_key'];

$store_info =Stores::Select('technify_store_id','name','store_url')->where('uuid',$license_key)->first();


if( !empty($order_info) ) {

$store_id = $store_info->technify_store_id;

$order_id = $order_info['order_id'];
$city 	  = strtolower($order_info['shipping_address']['city']);
$address  = strtolower($order_info['shipping_address']['address']);
$check_order_shippied = Shipping::where(['store_id' => $store_id, 'order_id' => $order_id, 'status' => '1'])->first();

if(!$check_order_shippied) {

$data = Helper::getcity($address, $city, $store_id); //city and courier company name

if( !empty($data) && $data !== '' ) {

$get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);

if($get_store_spec && isset($get_store_spec['shippingdetails'])) {
// $order_info['orderinfo']['store_name'] = $get_store_spec['store_info']['name'];
// $order_info['orderinfo']['store_url'] = $get_store_spec['store_info']['url'];
//$order_info['customer']['email'] = str_replace(' ', '', $order_info['customer']['email']);

$city      = strtolower($data['city']);
$address   = strtolower($order_info['shipping_address']['address']);
$telephone = strtolower($order_info['customer']['telephone']);
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
'license_key' => $license_key,
'datapacket'   => $order_info
];

$courier_company = $data['courier_company'];

$courier_response = $this->$courier_company($shipping_json);




if( $courier_response['response'] ) {

$tracking_id = $courier_response['tracking_id'];
$order_id    = $courier_response['order_id'];
$store_url   = $courier_response['store_url'];
$courier_company = $courier_response['courier_company'];
$customer_email = $order_info['customer']['email'];
$data = ['order_id' => $order_id, 'tracking_id' => $tracking_id, 'courier_company' => $courier_company, 'courier_domain' => $courier_company];

$support_email = Stores::select('email', 'support_email')->where('technify_store_id', $store_id)->first();

if($support_email) {
$emails = str_replace(" ", "", explode(',', $support_email->support_email));   // send emails for order success
foreach ($emails as $email) {
dispatch(new SendOrderSuccess($data, $email));
}
}

$datapacket = ['shipped_to' => $courier_company, 'tracking_id' => $tracking_id, 'order_id' => $order_id, 'address' => $address, 'city' => $city, 'telephone' => $telephone, 'email' => $customer_email];

$payload = ['action' => 'updateOrder', 'response' => true, 'dataPacket' => $datapacket];
$url = $get_store_spec['store_info']['api_endpoint'];

Helper::curlRequest($url, json_encode($payload, true));
$sms_payload=['store_id'=>$store_id,'order_id'=>$order_id,'tracking_id'=>$tracking_id,'courier_company'=>$courier_company,'telephone'=>$telephone,'store_name'=>$store_info->name];

$sms = Helper::sendSmsOnOrderShipped($sms_payload);
//return $sms;
$response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['successMessage' => 'ORDER SUCCESSFULLY SHIPPED TO '. strtoupper($courier_response['courier_company']) .' CHECK EMAIL OR VISIT YOUR TECHNIFY DASHBOARD', 'courier_company' => strtoupper($courier_response['courier_company']), 'tracking_id' => $tracking_id]]);

} else {
$shipping_json = [
'action'          => 'orderfailure',
'timestamp'       => $timestamp,
'failure_address' => $order_info['shipping_address']['address'],
'failure_city'    => $order_info['shipping_address']['city'],
'datapacket'      => $order_info
];

$order_id        = $shipping_json['datapacket']['order_id'];
$store_id        = $store_id;
$telephone       = $shipping_json['datapacket']['customer']['telephone'];
$email       	 = $shipping_json['datapacket']['customer']['email'];
$failure_address = $shipping_json['failure_address'];
$failure_city    = $shipping_json['failure_city'];

$msg = $courier_response['errorMessage'].', '.$this->processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json);

$response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
}
}

} else {

$shipping_json = [
'action'          => 'orderfailure',
'timestamp'       => $timestamp,
'failure_address' => $order_info['shipping_address']['address'],
'failure_city'    => $order_info['shipping_address']['city'],
'datapacket'      => $order_info
];

$order_id        = $shipping_json['datapacket']['order_id'];
$store_id        = $store_id;
$telephone       = $shipping_json['datapacket']['customer']['telephone'];
$email 	         = $shipping_json['datapacket']['customer']['email'];
$failure_address = $shipping_json['failure_address'];
$failure_city    = $shipping_json['failure_city'];

$msg = $this->processOfOrderFailure($store_id, $order_id, $failure_address, $failure_city, $telephone, $email, $shipping_json);

$response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
}

} else {
$msg = 'Order already been shipped';
$response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
}

} else {
$msg = 'order does not exist';
$response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
}

return $response;
}