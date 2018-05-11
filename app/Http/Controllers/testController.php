<?php

namespace App\Http\Controllers;

use App\Models\Action;
use Illuminate\Http\Request;
use App\Models\Testapicar;
use App\Models\Shipping;
use Helper;
use App\Http\Helpers\apphelper;
use App\Http\Controllers\Api\ApiCronController;

class testController extends Controller
{

    public function insert(){

        $methods = Action::select('method')->get();

        foreach($methods as $meth) {

            Action::where('method', $meth->method)
                ->update([
                    'action' => $meth->method

                ]);
        }
    }
    public function test(){
        $url="http://api.technify.pk/smsmodule";
        $payload="{
	\"action\": \"sendSmsToCustomer\",
	\"timestamp\": \"2017-08-08\",
	\"dataPacket\": {
		\"store_id\": 100005,
		\"mobile_number\": \"03363136361\",
		\"sms_body\": \"sdsdd\"
	}
}";
        $username="ahsans895@gmail.com";
        $password ="ahsan11";

        $sendSmsTracking= Helper::curlRequestWithBasicAuth($url, json_decode($payload), $username, $password);
        dd($sendSmsTracking);
    }

    public function getAuthorization(Request $request)
    {
        // $getPlatform = $this->getPlatform($_GET['license_key']);
        $payload = json_decode(file_get_contents("php://input"), true);  // dd($payload);
        $action  = $payload['action'];

        if( json_last_error() === 0 ) {
            $email = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
            $pass  = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';

            $authuntication = ['email' => $email, 'password' => $pass, 'action' => $action];
            $check_external_call = Action::where(['action' => $action, 'call' => 0])->first();

            if( count($check_external_call) > 0 ) {
                $license_key = $payload['license_key'];
                $authuntication['license_key'] = $license_key;
            }

            $requestController = new requestValidationController();
            $checkValidation = $requestController->validateAuthentication($authuntication);

            if( $checkValidation['response'] ) {

                $getPassword = User::select('password')->where('email', $email)->first();

                if ($getPassword) {
                    $getPassword = $getPassword->password;
                    $password = Hash::check($pass, $getPassword);

                    if(!$password) {
                        $msg = ['errorMessage' => 'Password required/donot match our records'];
                        $response = Helper::ifValidationFalse($action, $checkValidation);
                    } else {
                        $data = User::where('email', $email)->first();
                        $response = array_merge(Helper::constantResponse($action, 200, ''), ['dataPacket' => $data]);
                    }

                } else {
                    $msg = ['errorMessage' => 'Email required/donot match our records'];
                    $response = Helper::ifValidationFalse($action, $msg);
                }

            } else {
                $response = Helper::ifValidationFalse($action, $checkValidation);
            }
        } else {
            $msg = ['errorMessage' => Lang::get('smsmodule.jsonFormatError')];
            $response = Helper::ifValidationFalse('error', $msg);
        }

        return $response;
    }

    public function UpdateCourierStatus(){

        $filter = ['kangaroo'];
        $order_ids = Shipping::select('order_id', 'courier_name','store_id')->whereIn('courier_name',$filter)->whereRaw('MONTH(created_at) = MONTH(NOW())')->get();


        foreach($order_ids as $ids){

            $courier_name = $ids->courier_name;
            $order_id     = $ids->order_id;
            $store_id     = $ids->store_id;

            $cron_name = strtolower('cron'.$courier_name);

            $cronController= new ApiCronController();
            if(strtolower(method_exists($cronController,$cron_name))){
                $cronController->$cron_name($order_id,$store_id,$courier_name);

            }
        }
    }
}
