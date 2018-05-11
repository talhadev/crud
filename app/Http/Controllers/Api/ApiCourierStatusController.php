<?php
/**
 * Created by PhpStorm.
 * User: shehriyar
 * Date: 10/24/17
 * Time: 5:54 PM
 */

namespace App\Http\Controllers\Api;

use App\Models\Shipping;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

class ApiCourierStatusController extends Controller
{
    public static function cronTcs($order_id,$store_id, $courier_name){

        $order_tracking_id= Shipping::select('order_tracking_id')->where('order_id',$order_id)->first();

        $get_order_status = Helper::GetTcsOrderStatus($order_tracking_id->order_tracking_id);
        dd($get_order_status);
        $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);
        if(isset($get_store_spec['shippingdetails'])) {
            $courier_status = $get_store_spec['shippingdetails']['variables']['courierStatusForDelivered'][$courier_name];
            foreach ($courier_status as $key => $value) {
                if($get_order_status!=null) {

                    if ($get_order_status==$value) {

                        $order_status =Shipping::where('order_id', $order_id)->update(['courier_status' => 'Delivered']);
                        if($order_status){
                            return 'Delivered';
                        }
                    }else{

                        Shipping::where('order_id', $order_id)->update(['courier_status' => $get_order_status]);
                        break;
                    }
                }else{
                    break;
                }
            }
        }
    }

    public static function cronLeopard($order_id,$store_id, $courier_name){

        $order_tracking_id= Shipping::select('order_tracking_id')->where('order_id',$order_id)->first();
        $get_order_status = Helper::GetOrderStatusLeopard($order_tracking_id->order_tracking_id);

        $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);
        if(isset($get_store_spec['shippingdetails'])) {
            $courier_status = $get_store_spec['shippingdetails']['variables']['courierStatusForDelivered'][$courier_name];
            foreach ($courier_status as $key => $value) {
                if($get_order_status!=null) {
                    if ($get_order_status==$value) {

                        $order_status = Shipping::where('order_id', $order_id)->update(['courier_status' => 'Delivered']);
                        if($order_status){
                            return 'Delivered';

                        }
                    }else{
                        $order_status=Shipping::where('order_id', $order_id)->update(['courier_status' => $get_order_status]);
                        if($order_status){
                            return $get_order_status;

                        }
                    }
                }else{
                    $order_status=Shipping::where('order_id', $order_id)->update(['courier_status' => 'Not available']);
                    if($order_status){
                        return 'Not available';

                    }
                }

            }
        }
    }
    public static function cronKangaroo($order_id,$store_id,$courier_name){
        //$order_tracking_id= Shipping::select('order_tracking_id')->where('order_id',$order_id)->first();

        $get_order_status = Helper::GetKangrooOrderStatus($order_id);
        dd($get_order_status);
        $get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);
        if(isset($get_store_spec['shippingdetails'])) {
            $courier_status = $get_store_spec['shippingdetails']['variables']['courierStatusForDelivered'][$courier_name];
            foreach ($courier_status as $key => $value) {
                if($get_order_status!=null) {
                    if ($get_order_status==$value) {

                        $order_status = Shipping::where('order_id', $order_id)->update(['courier_status' => 'Delivered']);
                        if($order_status){
                            return 'Delivered';

                        }
                    }else{
                        $order_status=Shipping::where('order_id', $order_id)->update(['courier_status' => $get_order_status]);
                        if($order_status){
                            return $get_order_status;

                        }
                    }
                }else{
                    $order_status=Shipping::where('order_id', $order_id)->update(['courier_status' => 'Not available']);
                    if($order_status){
                        return 'Not available';

                    }
                }

            }
        }
    }



}
