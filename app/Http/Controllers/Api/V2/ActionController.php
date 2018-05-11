<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\requestValidationController;
use Helper;
use App;
use Hash;
use Lang;

// MODELS
use App\Models\Action;
use App\User;

class ActionController extends Controller
{
    public $error = false;
    protected $payload, $email = '', $pass = '', $action = 'error';

    function __construct(Request $request)
    {
        $authResponse = $this->getAuthorization($request);
        
        if($authResponse['statusCode'] == 500) {
            $this->error = $authResponse;
        }        
    }

    // check Authuntication
    public function getAuthorization(Request $request)
    {   
    	if(isset($request['payload'])) {

            $this->payload = json_decode($request['payload'], true); 
        } else {
            $this->payload = json_decode(file_get_contents("php://input"), true); 
        }
        
        if( json_last_error() === 0 ) {

        	if( isset($_GET['license_key']) ) {
	        	$license_key = $_GET['license_key'];
		        $get_platform = Helper::getStoreInfo($license_key, 'platforms'); 
		        
		        if( $get_platform['response'] ) {
		            $platform = ucfirst($get_platform['storeInfo']->platforms).'Controller';	
		            $namespace = 'App\\Http\\Controllers\Connector\\';
		            $controller_name = App::make( $namespace.$platform );
		            
		            $this->payload = (new $controller_name)->index( $license_key, $this->payload );		            
		            if(isset($this->payload['response']) && $this->payload['response'] == false) {
		                $packet = ['errorMessage' => $this->payload['errorMessage']];
		                return Helper::ifValidationFalse('error', $packet);    
		            }                   
		        }  else {
		            $packet = ['errorMessage' => 'invalid license'];
		            return Helper::ifValidationFalse($this->action, $packet);
		        }
		    }

            if( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) ) {
                $this->email = $_SERVER['PHP_AUTH_USER'];    
                $this->pass  = $_SERVER['PHP_AUTH_PW'];    
            } else if( $request->headers->get('email') && $request->headers->get('pass') ) {
                $this->email = $request->headers->get('email');
                $this->pass  = $request->headers->get('pass');
            } else {
                $this->email = 'services@technify.pk';
                $this->pass  = 'user@technify';
            }

            $this->action  = isset($this->payload['action']) ? $this->payload['action'] : 'error';  
            $authuntication = ['email' => $this->email, 'password' => $this->pass, 'action' => $this->action];
            $check_external_call = Action::where(['action' => $this->action, 'call' => 0])->first();
            
            if( count($check_external_call) > 0 ) {
                $license_key = $this->payload['license_key'];
                $authuntication['license_key'] = $license_key;
            }                
            
            $requestController = new requestValidationController();
            $checkValidation = $requestController->validateAuthentication($authuntication);
            
            if( $checkValidation['response'] ) {  

                $getPassword = User::select('password')->where('email', $this->email)->where('isadmin', 1)->first();

                if ($getPassword) {
                    $getPassword = $getPassword->password;  
                    $password = Hash::check($this->pass, $getPassword);   

                    if($password) {
                        $data = User::where('email', $this->email)->first();                    
                        $response = Helper::ifApiSuccess($this->action, $data);
                    } else {
                        $msg = ['errorMessage' => 'Password required/donot match our records'];
                        $response = Helper::ifValidationFalse($this->action, $msg);      
                    }
                            
                } else {
                    $msg = ['errorMessage' => 'Email required/donot match our records'];
                    $response = Helper::ifValidationFalse($this->action, $msg);      
                }            
                
            } else {                
                $response = Helper::ifValidationFalse($this->action, $checkValidation);            
            }

        } else {
        	$msg = ['errorMessage' => 'JSON syntax error'];
            $response = Helper::ifValidationFalse($this->action, $msg);
        }

        return $response;  
    }

    // this function decide which functionality perform using action
    public function action()
    {   
        if($this->error) {
            $response = $this->error;
            return $response;
        }     
        @file_put_contents('payload.json', json_encode($this->payload));                
        $namespace = 'App\\Http\\Controllers\Api\\V2\\';                  
        $getaction = Action::where('action', $this->action)->first();  
        if( $getaction ){
            if( strtolower($getaction->action) == strtolower($this->action) ){  
                $data['controller'] = $getaction->controller;
                $data['method']     = $getaction->method;    
                          
            }       
            
            $controller_name = App::make( $namespace.$data['controller'] );
            $response = (new $controller_name)->{$data['method']}( $this->payload );


        } else {
            $msg = ['errorMessage' => Lang::get('shipping.invalidAction')];
            $response = Helper::ifValidationFalse($this->action, $msg);              
        }                                    
        
        return $response;            
    }
}
