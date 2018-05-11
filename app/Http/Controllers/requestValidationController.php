<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Validator;

// MODELS
use App\Models\Stores;

class requestValidationController extends Controller
{
    // validate store
    public function validateStore(array $validateStore)
    {
        $rules = [
            'store_id'   => 'required'            
        ];

        $validator = Validator::make($validateStore['dataPacket'], $rules);

        if($validator->fails()) {
            $response = ['response' => false, 'errorMessage' => $validator->errors()->all()[0]];
        } else {

            $store_id = $validateStore['dataPacket']['store_id'];

            $store = Stores::where('technify_store_id', $store_id)->first();
            
            if( count($store) > 0 ) {
                $response = ['response' => true];
            } else {
                $response = ['response' => false, 'errorMessage' => 'invalid store id'];
            }            
        }

        return $response;
    }

    // validate store
    public function validateStoreEmail(array $validateStoreEmail)
    {
        $rules = [
            'email'   => 'required'            
        ];

        $validator = Validator::make($validateStoreEmail['dataPacket'], $rules);

        if($validator->fails()) {
            $response = ['response' => false, 'errorMessage' => $validator->errors()->all()[0]];
        } else {

            $email = $validateStoreEmail['dataPacket']['email'];   

            $store = Stores::where('email', $email)->first();
            if( count($store) > 0 ) {
                $response = ['response' => true];
            } else {
                $response = ['response' => false, 'errorMessage' => 'invalid Email'];
            }            
        }

        return $response;
    }

    // validate Store information
    public function validateStoreInfo($request, $action = false, $id = false)
    {           
        $rules = array(
//            'technify_store_id' => 'required|unique:stores',
//            'name'              => 'required',
//            'store_url'         => 'required',
//            'address'           => 'required',
//            'telephone'         => 'required',
//            'email'             => 'required|email|unique:stores|unique:users',
//            'support_email'     => 'required',
        );
        if($action == "updateStore") {  
            $rules['email'] = 'required|email|unique:stores,email,'.$id;
            $rules['technify_store_id'] = 'required|unique:stores,technify_store_id,'.$id;
        }
        $validate = Validator::make($request, $rules);

        if($validate->fails()) {
            $response = ['response' => false, 'errorMessage' => $validate->errors()->all()[0]];
        } else {
            $response = ['response' => true];            
        }

        return $response;
    }

    // validate store uuid/license_key 
    public function validateStoreUuid($payload)
    {
        $rules = [
            'license_key'   => 'required|exists:stores,uuid'            
        ];

        $validator = Validator::make($payload['dataPacket'], $rules);

        if($validator->fails()) {
            $response = ['response' => false, 'errorMessage' => $validator->errors()->all()[0]];
        } else {
            $response = ['response' => true];      
        }

        return $response;
    }

    // validate store and order id
    public function validateStoreAndOrderID($payload)
    {
        $validateStore = $this->validateStore($payload);

        if($validateStore['response']) {        
            $rules = array(
                'order_id' => 'required',
            );        
            $validate = Validator::make($payload['dataPacket'], $rules);
            if($validate->fails()) {
                $response = ['response' => false, 'errorMessage' => $validate->errors()->all()[0]];
            } else {
                $response = ['response' => true];            
            }    
        } else {
            $response = ['response' => false, 'errorMessage' => $validateStore['errorMessage']];
        }

        return $response;
    }

    public function validateAuthentication($authentication)
    {
        $rules = [
            'email'       => 'required',
            'password'    => 'required',
            'action'      => 'required',
        ];
        if( isset($authentication['license_key']) ) {
            $rules['license_key'] = 'required|exists:stores,uuid';
        }

        $validator = Validator::make($authentication, $rules);

        if($validator->fails()) {
            $response = ['response' => false, 'errorMessage' => $validator->errors()->all()[0]];

        } else {
            $response = ['response' => true];
        }
        return $response;
    }

    public function shipOrder($payload)
    {
        $rules = [
            'license_key' => 'required',
            'dataPacket.order_id' => 'required',
            'dataPacket.order_status_id' => 'required'
        ];
        
        $validator = Validator::make($payload, $rules);

        if($validator->fails()) {
            $response = ['response' => false, 'errorMessage' => $validator->errors()->all()[0]];
        } else {
            $response = ['response' => true];      
        }

        return $response;
    }
    
}
