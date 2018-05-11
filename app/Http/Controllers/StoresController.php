<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendNotifyStore;
use auth;
use Redirect;
use Mail;
use Config;
use Helper;

// Models
use App\Models\Stores;
use App\Models\Shipping;
use App\User;

class StoresController extends Controller
{
	protected $module_active, $platform; 
    private $username = 'services@technify.pk', $password = 'user@technify';
    /**
    *  if user not login redirect to login page
    */
    public function __construct()
    {    	      	
        $this->middleware('admin', [
            'only' => ['index', 'create', 'store', 'update', 'destroy', 'edit', 'show', 'storeSpec']
        ]);

        $this->module_active = ['' => 'please select', '1' => 'active', '0' => 'inactive'];
        $this->platform      = ['' => 'please select', 'opencart' => 'Opencart', 'newopencart' => 'New Opencart', 'shopify' => 'Shopify', 'magentosoap' => 'Magento Soap', 'magentorest' => 'Magento Rest', 'bigcommerce' => 'Big Commerce', 'woocommerce' => 'Woo Commerce', 'custom' => 'Custom'];
    }         

	public function index()
	{	
		$stores = Stores::latest()->paginate(50);  
        return view('store.index', compact('stores'));
	}

	public function create()
	{
		$module_active = $this->module_active;
        $platform = $this->platform;
		return view('store.create', compact('module_active', 'platform'));
	}

	public function store(Request $request)
	{	
		$this->validate($request, ['technify_store_id' => 'required|unique:stores', 'name' => 'required',  'address' => 'required',  'telephone' => 'required', 'email' => 'required|unique:stores|unique:users|email', 'store_url' => 'required', 'support_email' => 'required']);
		$input = $request->all();
		
		$emailResp = $this->validateEmails($input['support_email']);   
		if(!$emailResp){		
			return redirect('store/create')->with('error_flash', 'Support email invalid')->withInput($request->all());
		}
		$input['password'] = $this->generateRandomString(7);   
		$input['uuid']	   = uniqid('', true);  // generate 23 characters including dot						
		$data = ['name' => $input['name'], 'email' => $input['email'], 'password' => bcrypt($input['password']), 'isadmin' => (isset($input['isadmin'])) ? $input['isadmin'] : 0 ];
		$user = User::create($data);

		$input['user_id'] = $user->id;
		//create store and store user
		$store = Stores::create($input);		

		if( $store && $user ) {
			$email = $store->email;

			$viewData = ['name' => $store->name, 'store_url' => $store->store_url, 'password' => $store->password, 'email' => $store->email, 'support_email' => $store->support_email, 'uuid' => $store->uuid, 'created_at' => $store->created_at, 'dashboard_url' => Config::get('urls.app_urls.app_url')];

			dispatch(new SendNotifyStore($viewData, $email));    			

            @file_put_contents('stores/jsons/' . $store->technify_store_id . '.json', file_get_contents('stores/jsons/sample.json'));
            @file_put_contents('cities/' . $store->technify_store_id . '.php', json_encode($store, true));
		}

		return redirect('/store')->with('flash_message', 'Store/User Added Successfully!');
	}

    public function show($id)
    {
        $store = Stores::findorfail($id);

        return view('store.show', compact('store'));
    }

	public function edit($id)
    {	
    	$module_active = $this->module_active;
        $platform      = $this->platform;
        $store = Stores::where('id', $id)->first();
        if( count($store) > 0 ) {
        	$store['isadmin'] = User::select('isadmin')->where('email', $store->email)->first()->isadmin;	
        	
        }         
        return view('store.edit', compact('store', 'module_active', 'platform')); 
    }

    public function update(Request $request, $id)
    {        
    	$this->validate($request, ['technify_store_id' => 'required|unique:stores,technify_store_id,'.$id, 'name' => 'required',  'address' => 'required',  'email' => 'required|email|unique:stores,email,'.$id]);
        $input = $request->all();			
		$emailResp = $this->validateEmails($input['support_email']);   
		if(!$emailResp){		
			return redirect('store/'.$id.'/edit')->with('error_flash', 'Support email invalid')->withInput($request->all());
		}
        $store = Stores::findorfail($id);
        $store->update($request->all());

        return redirect('/store')->with('flash_message', 'Store updated successfully!');
    }

    public function destroy($id)
    {
        $store = Stores::find($id);
        $user  = User::find($store->user_id);

        if($user && $store) {
        	if(@file_get_contents('stores/jsons/'.$store->technify_store_id.'sample.json')){
        		unlink('stores/jsons/'.$store->technify_store_id.'sample.json');
        	}
        	if(@include('cities/'.$store->technify_store_id.'.php')) {
        		unlink('cities/'.$store->technify_store_id.'.php');
        	}        	
        	$user->delete();
	        $store->delete();
	        return Redirect::back()->with('flash_message', 'Store/User deleted successfully');
        } else {
        	return Redirect::back()->with('error_flash', 'Something wrong');
        }        
    }

    // pull vendor order status
    public function pullOrderStatus($license_key)
    {        
        $payload = Helper::createCurlPayload('pullOrderStatus', []); 
        $payload['license_key'] = $license_key;
        
        $url = Config::get('urls.app_urls.end_point');          
        $useragent = 'pull order status';         
        $response = json_decode(Helper::curlRequestWithBasicAuth($url, $payload, $this->username, $this->password), true);         
        
        if( $response['statusCode'] == 200 ) {
            return redirect('/store')->with('flash_message', $response['dataPacket']['successMessage']);
        } else {
            return redirect('/store')->with('error_flash', $response['errorMessage']);
        }
    }

    // get License key from extension
	public function getLicense(Request $request)
	{			
		$json = json_decode($request->license, true); 
		$store = Stores::where('uuid', $json['license_key'])->first();  

		if(isset($store)){
			file_put_contents('license.json', $store, true); 
			$response = $store->technify_store_id;
		} else {
			$response = false;
		}		
		return $response;
	}

	// check authorization from extension
	public function authorizeKey(Request $request)
	{	
		$json = json_decode($request->auth, true);
		$checkkey = Stores::where('uuid', $json['license_key'])->first();
		
		if($checkkey) { 
			return $checkkey->uuid;
		} else {
			return;
		}
	}

	// generate randon numebr and letters
	function generateRandomString($length) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	// validate multiple emails comma seprated
	public function validateEmails($emails)
	{	
		$emails = str_replace(' ', '', $emails);
		$emails = explode(",",$emails);  
		foreach ($emails as $key => $value) {		
			if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {		 
				return false;
				break;
			} 
		}		
		return true;
	}

	// check order status is completed
	public function orderStatus(Request $request)
	{		
		$data = json_decode($request->payload, true);
		$filter = ['order_id' => $data['order_id'], 'store_id' => $data['store_id']];
		$get_success_order = Shipping::select('order_id', 'status')->where($filter)->first();
		
		if( $get_success_order && $get_success_order->status == '1' ) {
			$response = ['status' => '1', 'order_id' => $get_success_order->order_id];
		} else if( $get_success_order && $get_success_order->status == '2' ) {
			$response = ['status' => '2', 'order_id' => $get_success_order->order_id];
		} else {
			$response = ['status' => '0'];
		}
		
		return $response;
	}

	// get all store spec
	public function storeSpec()
	{			
		$specs = [];
		$stores = Stores::select('technify_store_id')->get();
		
		if($stores){
			foreach ($stores as $key => $store) {			
				$specs[$store->technify_store_id] = json_decode(file_get_contents('stores/jsons/'.$store->technify_store_id.'sample.json'),true );
			}
		}
		
		return view('store/spec/index', compact('specs'));		
	}
}
