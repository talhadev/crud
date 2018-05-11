<?php

namespace App\Http\Controllers\Connector;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;
use SoapClient;

// MODELS
use App\Models\Shipping;
use App\Models\VendorOrderStatus;

class MagentosoapController extends Controller
{
    protected $license_key, $order_obj;
    public function index($license_key, $payload)
    {   
        $this->license_key = $license_key;
        @file_put_contents('magentosoap.json', json_encode($payload));
        $this->order_obj = $payload['dataPacket']['entity']['object'];

        if( $this->order_obj['state'] == null || $this->order_obj['status'] == null ){  
            $this->order_obj['state'] = 'new';
            $this->order_obj['status'] = 'pending';
        }
        
        $store = Helper::getStoreInfo($license_key);
        $store_info = $store['storeInfo']; 
        $store_id = $store_info['technify_store_id'];
        $order_id = $this->order_obj['increment_id'];        
        $order_status_id = $this->order_obj['status'];        
        $endpoint = $store_info['endpoint'];
        $filter = compact('store_id', 'order_id');
        
        $transform_order_object = $this->transformOrderObject();  
        if( $transform_order_object['dataPacket']['customer']['email'] == null || $transform_order_object['dataPacket']['customer']['email'] == '') {
            $transform_order_object['dataPacket']['customer']['email'] = 'example@example.com';
        }        
        // check order exist
        $check_order_exist = Helper::checkOrderExist($filter);                  
    	if( $check_order_exist['response'] ){
			$order_fileds = Helper::splitOrderObjectToFields($transform_order_object, $store_id);        
            $check_order_exist['orderDetails']->update($order_fileds);                             	    	
		} else {            
            $order_fileds = array_merge($filter, Helper::splitOrderObjectToFields($transform_order_object, $store_id));            
            $order_fileds = array_merge($order_fileds, ['status' => 0]);
            Shipping::create($order_fileds);
		}
		
        $action = 'shipOrder';
        $datapacket = compact('order_id', 'order_status_id');
        $payload = Helper::createCurlPayload($action, $datapacket);
        $payload['license_key'] = $license_key;
        
    	return $payload;
    }

    public function pullOrderStatus($store_info)
    {	
        $auth = $store_info->auth;
        $endpoint = $store_info->endpoint;        

        if( $auth && $endpoint ) {

            $auth = json_decode($store_info->auth, true)['basic_auth'];            
            $username = $auth['username'];
            $password = $auth['pass'];

            $client = new SoapClient($endpoint.'?wsdl');        
            $session = $client->login($username, $password);  
            $param = ['sessionId' => $session, 'storeView' => 0];          
            $order_status = $client->call($session, 'serviceorder.getOrderStatuses', $param);              
            
            if( is_array($order_status) !== null ) {                
                foreach ( $order_status as $key => $value ) {    

                    $status = substr($value, strpos($value, '::') + 2);
                    $data = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $status, 'order_status' => $status, 'status' => 0, 'title' => ''];                
                    $filter = ['store_id' => $store_info->technify_store_id, 'order_status_id' => $status];
                    $get_order_status = VendorOrderStatus::where($filter)->first();                     
                    if( count($get_order_status) > 0 ) {
                        $get_order_status->update(['order_status' => $status]);
                    } else {
                        VendorOrderStatus::create($data);
                    } 
                } 
                $response = ['response' => true, 'orderStatus' => $order_status];
            } else {
                $response = ['response' => false, 'errorMessage' => 'something wrong with order status'];
            }
        } else {
            $response = ['response' => false, 'errorMessage' => 'please provide auth/endpoint'];
        }

    	return $response;    	
    }

    // magento soap order statuses fix
    public function orderStatus()
    {
    	return [
            'new' => 'New',
    		'pending' => 'Pending',
    		'pending_payment' => 'Pending payment',
    		'processing' => 'Processing',
            'complete' => 'Complete',
            'closed' => 'Closed',
            'canceled' => 'Canceled',
            'holded' => 'Holded',
            'payment_review' => 'Payment review',
            'fraud' => 'Fraud'
    	];
	}

    // update order status in big commerce vendor 
    public function updateOrderStatus($endpoint, $auth, $packet)
    {	    
        $store_id = $packet['store_id'];        
        $order_id = $packet['order_id'];
        $tracking_id = $packet['tracking_id'];        
                
        $order_status = Helper::getShippingSettingsOrderStatus($store_id, 'after_shipped');

        if( $order_status['response'] ) {

            $order_status = $order_status['orderStatusID'];
            $auth = json_decode($auth, true)['basic_auth'];
            $username = $auth['username'];
            $pass = $auth['pass'];        

            $client = new SoapClient($endpoint.'?wsdl');        
            $session = $client->login($username, $pass);  

            $track = ['carrier' => 'custom', 'title' => 'Technify services', 'tracking_id' => $tracking_id];
            $param = ['payload' => ['order_id' => $order_id, 'status' => $order_status, 'comment' => $packet['successMessage'], 'notify' => false, 'track' => $track]];
            $result = $client->call($session, 'serviceorder.update', $param);  
            
        } else {
            $response = ['response' => false, 'errorMessage' => 'something wrong with update order'];
        }        
                       
        return $response;
    }

    // tranform order object from nifi
    public function transformOrderObject()
    {   
        $nifi_endpoint = Config::get('urls.app_urls.nifi_endpoint');  
        $header = ['storeid-entity' => 'magentosoap_technify-order-spec'];
        $datapacket = json_decode(Helper::curl($nifi_endpoint, 'POST', $this->order_obj, $header), true);
        $payload['action'] = 'pushOrderObject';
        $payload['license_key'] = $this->license_key;         
        $payload['dataPacket'] = $datapacket;
        
        return $payload;
    }

    public function abc()
    {
        $endpoint = 'http://development.hub.com.pk/index.php/api/soap/index';
        $auth = '{"basic_auth":{"username":"technify","pass":"WrkPdbFDxLhUDpzhGgGZtkP"}}';
        $packet = ['successMessage' => 'ORDER SUCCESSFULLY SHIPPED TO leopard CHECK EMAIL OR VISIT YOUR TECHNIFY DASHBOARD', 'courier_company' => 'leopard', 'order_id' => '100000856', 'tracking_id' => '1234567', 'order_status' => 'processing', 'store_url' => 'https://google.com', 'store_id' => 200010];
        $this->updateOrderStatus($endpoint, $auth, $packet);
    }
}
