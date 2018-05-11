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

class ApiCronController extends Controller
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

                        Shipping::where('order_id', $order_id)->update(['courier_status' => 'Delivered']);
                        break;
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

                        Shipping::where('order_id', $order_id)->update(['courier_status' => 'Delivered']);
                        break;
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
    public static function cronBluex($order_id,$store_id, $courier_name){

        $order_tracking_id= Shipping::select('order_tracking_id')->where('order_id',$order_id)->first();
        $get_order_status = Helper::GetCNDetailsByReferenceNumberBlueEx($order_tracking_id->order_tracking_id, $store_id);
        if($get_order_status!=null) {
                    if ($get_order_status.contains('Delivered')) {
                        $test = Shipping::where('order_id', $order_id)->update(['courier_status' => 'Delivered']);
                        echo 'done with delivered';
                    }else{
                        Shipping::where('order_id', $order_id)->update(['courier_status' => $get_order_status]);
                    echo 'some other status';
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

                            Shipping::where('order_id', $order_id)->update(['courier_status' => 'Delivered']);
                            break;
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




}
