<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Shippinginfo;

class ApiController extends Controller
{
    public function api(Request $request) {

    	if( isset($request->payload) ) {

    		$json = json_decode($request->payload, true); 

    		$rules = array(
	            'action' => 'required',
	            'timestamp' => 'date_format:Y-m-d H:i:s',
	            'entity.order_id' => 'required',
	            'entity.shipping' => 'required',
	        );

			$validation = Validator::make($json, $rules);		

			if ($validation->fails()) {
           		$response = $validation->errors();
        	} else {
                $response = 'validate';
            }	

    	}    	    	
		return response($response);
    }

    // read json, technify store information 
    public function technifystore(Request $request) {

    	/*$technifystore_url = 'http://files.technify.pk/stores/100013/order/order.json';       
    	$curl = curl_init($technifystore_url); 
	    $curl_response = curl_exec($curl);		
	    curl_close($curl);
	    $response = json_decode($curl_response, true);
	    return $response;*/    
	    $json = json_decode($request->payload, true); 
	    $store_id = $json['entity']['store_id'];  
	    return file_get_contents('http://files.technify.pk/stores/'.$store_id.'/specs/attribute/attribute.json');

    }

    // read json on technify store
    public function readJsonPushToDB()
    {
    	$technifystore_url = 'http://files.technify.pk/Ahsan.json';       
    	$response = json_decode(file_get_contents($technifystore_url), true);
		
    	Shippinginfo::create($response['Shipping']);

	    return $response['Shipping'];
    }

    public function readspec(Request $request)
    {   
        if( isset($request->payload) ) {

            $json = json_decode($request->payload, true); 
            $city = $this->getcity($json['shipping_address']['address']);

        }               
        return response($city);
    }

    // find address
    public function getcity($context)
    {
        $context_parts = array_reverse(explode(" ", $context)); 
        $address = 'n/a';
        foreach ($context_parts as $key => $value) {

            if( $value == "karachi" ){
                $address = $value;
                break;
            } 
        }

        return $address;
    }
}
