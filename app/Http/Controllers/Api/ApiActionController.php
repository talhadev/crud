<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Hash;
use App\Http\Controllers\requestValidationController;
use Helper;
use App\Models\Stores;
use Lang;
use App;

// MODELS 
use App\User;
use App\Models\Action;

class ApiActionController extends Controller
{
    protected $payload;
    function __construct(Request $request)
    {
        $authResponse = $this->getAuthorization($request);

        if($authResponse['statusCode'] == 500) {

        }        
    }

    // check Authuntication
    public function getAuthorization(Request $request)
    {    	
        if (isset($_SERVER['REDIRECT_QUERY_STRING'])) {
            $license_key = $_SERVER['REDIRECT_QUERY_STRING'];
            $store_name = Stores::select('platforms','uuid','store_url')->where('uuid', $license_key)->first();
            
            if (count($store_name) > 0) {

                if(isset($request['payload'])) {
                    $getRequestData = $request['payload'];
                    file_put_contents('file.json',$getRequestData);
                }else {
                	$getRequestData = file_get_contents("php://input");
                    file_put_contents('payload.json', $getRequestData);
                }                
                $payload = $getRequestData;      
                $namespace = 'App\Http\Controllers\platforms\\';
                $controller_name = App::make($namespace . $store_name->platforms . 'Controller');
                $response = (new $controller_name)->index($payload, $store_name->uuid, $store_name->store_url);                
            } else {
                $msg = ['errorMessage' => 'Invalid license key'];
                $response = Helper::ifValidationFalse("platform", $msg);                
            }
        } else { 
            $email = $_SERVER['PHP_AUTH_USER'];
            $pass = $_SERVER['PHP_AUTH_PW'];
            $payload = json_decode(file_get_contents("php://input"), true);  // dd($payload);
            $action = $payload['action'];

            $authuntication = ['email' => $email, 'password' => $pass, 'action' => $action];
            $check_external_call = Action::where(['action' => $action, 'call' => 0])->first();

            if (count($check_external_call) > 0) {
                $license_key = $payload['license_key'];
                $authuntication['license_key'] = $license_key;

            }

            $requestController = new requestValidationController();
            $checkValidation = $requestController->validateAuthentication($authuntication);

            if ($checkValidation['response']) {

                $getPassword = User::select('password')->where('email', $email)->first();

                if ($getPassword) {
                    $getPassword = $getPassword->password;
                    $password = Hash::check($pass, $getPassword);

                    if (!$password) {
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
            return $response;
        }
    }


    public function action(Request $request)
    {   
        $getRequestData = file_get_contents("php://input");
        @file_put_contents('payload.json', $getRequestData);
		$payload = json_decode($getRequestData, true);		
		$namespace = 'App\Http\Controllers\Api\\';
	    
        if( json_last_error() === 0 ) {
            $action  = $payload['action'];   
            $getaction = Action::where('action', $action)->first();

            if(count($getaction) > 0){
                if($getaction->action == $action){
                    $data['controller'] = $getaction->controller;
                    $data['method']     = $getaction->method;

                }
                $controller_name = App::make($namespace . $data['controller']);
                $method_name=$data['method'];
                $response = (new $controller_name)->$method_name($payload);

            } else {
                $msg = Lang::get('shipping.invalidAction');
                $response = array_merge(Helper::constantResponse($action, 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);            
            }                                    
        } else {                
            $msg = Lang::get('shipping.jsonFormatError');
            $response = array_merge(Helper::constantResponse('error', 500, $msg), ['dataPacket' => ['errorMessage' => $msg]]);            
        }  

        return $response;            
    }
}
