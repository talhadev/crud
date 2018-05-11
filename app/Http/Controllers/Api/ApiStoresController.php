<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\requestValidationController;
use App\Jobs\SendNotifyStore;
use Helper;
use Lang;
use Config;

// Models
use App\Models\Stores;
use App\Models\Shipping;
use App\User;

class ApiStoresController extends Controller
{
	// register Store
    public function registerStore($payload)
    {                             	
        $action = $payload['action'];
        $payload = $payload['dataPacket'];
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreInfo($payload);
        
        if ($checkValidation['response']) {

            $password = Helper::generateRandomCode('string', 7);
            $pinCode  = strtoupper(Helper::generateRandomCode('number', 6));

            $user_data = ['name' => $payload['name'], 'email' => $payload['email'], 'password' => bcrypt($password), 'isadmin' => 0];

            $user = User::create($user_data);
            $store_data = ['user_id' => $user->id, 'pin_code' => $pinCode, 'password' => $password, 'uuid' => uniqid('', true)];

            $store = Stores::create(array_merge($payload, $store_data));

            if($store_data && $user_data) {                
                $email = $store->email;

                $store = json_decode($store, true);

            	$store['dashboard_url'] = Config::get('urls.app_urls.app_url');

                dispatch(new SendNotifyStore($store, $email));    			

                @file_put_contents('stores/jsons/' . $store['technify_store_id'] . 'sample.json', file_get_contents('stores/jsons/100022.json'));
                @file_put_contents('cities/' . $store['technify_store_id'] . '.php', json_encode($store, true));

                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['successMessage' => 'User/Store '. $store['technify_store_id'] .' created Successfully']]);
            } else {
                $msg = 'Something Wrong';
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);                
            }

        } else {            
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }                   

        return $response;
    }

	// get All stores
    public function getStores($payload)
    {
    	$store = Stores::all();
        $action = $payload['action']; 
        
        if( count($store) > 0 ) {
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['Stores' => $store]]);
        } else {
            $msg = Lang::get('shipping.notFound', ['data' => 'Stores']);
            $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
        }
        return $response;
    }

    // get Store By ID
    public function getStoreByID($payload)
    {
        $store_id = $payload['dataPacket']['store_id'];
        $action = $payload['action'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload);

        if ($checkValidation['response']) {
            $store = Stores::where('technify_store_id', $store_id)->first();

            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store' => $store]]);
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;
    }
    // get Store By email
    public function getStoreByEmail($payload)
    {                  
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreEmail($payload);
        $action = $payload['action'];
        if($checkValidation['response']) {
            $email = $payload['dataPacket']['email']; 
            $store = Stores::where('email', $email)->first();

            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store' => $store]]);      
        } else {            
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }
    // get Store By ID or email
    public function editStore($payload)
    {                   
        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStore($payload); 
        $action = $payload['action']; 
        if($checkValidation['response']) {
        	$store_id = $payload['dataPacket']['store_id'];
            $store = Stores::where('technify_store_id', $store_id)->first();

            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store' => $store]]);      
        } else {            
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }
    public function checkIfOrderExists($payload)
    {
        $action = $payload['action'];
        $order_status = $payload['dataPacket'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);

        if ($checkValidation['response']) {

            $filter = ['order_id' => $order_status['order_id'], 'store_id' => $order_status['store_id']];
            $check_order = Shipping::where($filter)->first();

            if (count($check_order) > 0) {
                $msg = 'Exists';
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['successMessage' => $msg]]);
                return $response;

            } else {
                $msg = 'Order Does not exists';
                $response = array_merge(Helper::constantResponse($action, 500, ''), ['dataPacket' => ['successMessage' => $msg]]);
                return $response;
            }
        }else{

            $response = Helper::ifValidationFalse($action, $checkValidation);
             return $response;
        }
    }
    // update store
    public function updateStore($payload)
    {           
        $action         = $payload['action'];
        $payload        = $payload['dataPacket'];
        $store_id       = $payload['technify_store_id'];
        $id 	        = $payload['id'];   // for store update


        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreInfo($payload , $action , $id);
        if($checkValidation['response']) {
            $updateStore = Stores::where('technify_store_id', $store_id)->first();
            if(count($updateStore) > 0) {
               $updateUser = User::where('id', $updateStore->user_id)->first();
               dd($updateUser);

                $data = ['name' => $payload['name'],'email' => $payload['email']];

                $updateStore->update($payload);
                $updateUser->update($data);
                $response = array_merge(Helper::constantResponse('action', 200, ''), ['dataPacket' => ['successMessage' => 'User/Store '. $updateStore->id .' updated Successfully']]);

            } else {
                $msg = 'Something Wrong';
                $response = array_merge(Helper::constantResponse('action', 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);
            }
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        return $response;   
    }
    // get License key from extension (return store ID)
    public function getLicense($payload)
    {
        $action = $payload['action'];
        $get_license = $payload['dataPacket'];


        $requestController = new requestValidationController();
        $checkValidation   = $requestController->validateStoreUuid($payload);

        if( $checkValidation['response']) {
            $store = Stores::where('uuid', $get_license['license_key'])->first();

            file_put_contents('license.json', $store->uuid, true);
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store_id'=>$store->technify_store_id,'license_key' => $store->uuid]]);
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }
    // check authorization from extension
    public function authorizeKey($payload)
    {   
        $action = $payload['action'];
        $authorize_key = $payload['dataPacket'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreUuid($payload);

        if($checkValidation['response']) {
            $store = Stores::where('uuid', $authorize_key['license_key'])->first();
        
            $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => ['store_id' => $store->technify_store_id]]);
        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }

        return $response;
    }
    
    // check order status is completed
    public function checkOrderStatus($payload)
    {   
        $action = $payload['action'];
        $order_status = $payload['dataPacket'];

        $requestController = new requestValidationController();
        $checkValidation = $requestController->validateStoreAndOrderID($payload);

        if( $checkValidation['response'] ) {

            $filter = ['order_id' => $order_status['order_id'], 'store_id' => $order_status['store_id']];
            $get_shipped_order = Shipping::select('order_id', 'status')->where($filter)->first();
            
            if( $get_shipped_order && $get_shipped_order->status == '1' ) {
                $data = ['status' => '1', 'order_id' => $get_shipped_order->order_id];
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $data ]);
            } else if( $get_shipped_order && $get_shipped_order->status == '2' ) {
                $data = ['status' => '2', 'order_id' => $get_shipped_order->order_id];
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $data ]);
            } else {
                $data = ['status' => '0'];
                $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $data ]);
            }

        } else {
            $response = Helper::ifValidationFalse($action, $checkValidation);
        }
        
        return $response;
    }
}
