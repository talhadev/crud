<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use App;

class VendorDashboard extends Controller
{
    // pull vendor all order status
    public function pullOrderStatus($payload)
    {	    	
        $action = $payload['action'];       
        $license_key  = $payload['license_key'];

        $store_info = Helper::getStoreInfo($license_key)['storeInfo']; 
        $store = $store_info->name;        
        
        $platform = ucfirst($store_info->platforms).'Controller';
        $namespace = 'App\\Http\\Controllers\\Connector\\'; 
        $controller_name = App::make( $namespace.$platform ); 
        $order_status = (new $controller_name)->pullOrderStatus($store_info);
        
        if($order_status['response']) {
            $datapacket = ['successMessage' => $store.' order status updated'];                                                                   
            $response = Helper::ifApiSuccess($action, $datapacket);
        } else {
            $response = Helper::ifValidationFalse($action, $order_status);
        }
                        
        return $response;
    }
}
