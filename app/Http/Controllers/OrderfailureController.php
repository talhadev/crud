<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Config;
use Helper;
use auth;
use Carbon\Carbon;

//MODELS
use App\Models\Shipping;
use App\Models\Orderfailure;
use App\Models\Stores;

class OrderfailureController extends Controller
{
    protected $username;
    protected $password;
    /**
    *  if user not login redirect to login page
    */
    public function __construct()
    {           
        $this->middleware('auth', [
            'only' => ['index', 'create', 'store', 'update', 'destroy', 'proceed', 'edit']
        ]);

        $this->username = 'ahsans895@gmail.com';
        $this->password = 'ahsan11';
    }         

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];
        $email = auth::user()->email;   
        $store = Stores::where('email', $email)->first();         

        $filters = ['store_id' => $store->technify_store_id];        
        $get_store_spec = json_decode(Helper::getStoreSpec($store->technify_store_id), true);

        if( isset($get_store_spec['view']['button']) ){
            $data = $get_store_spec['view']['button'];
        }

        $order_success   = Shipping::where($filters)->where('status', '1')->latest()->paginate(20);              
        $order_cancelled = Shipping::where($filters)->where('status', '2')->latest()->paginate(20);          
        $order_failure   = Orderfailure::where($filters)->where('status', '0')->latest()->paginate(20);  
        
        return view('orderfailure.index', compact('order_success', 'order_failure', 'store', 'order_cancelled', 'data'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {           
        $failure_order = Orderfailure::findorfail($id);         
        $jsonOrder = json_decode($failure_order->orderinfo, true);  
        $failure_order['price'] = end($jsonOrder['datapacket']['total'])['value'];
        $failure_order['country'] = $jsonOrder['datapacket']['shipping_address']['country'];
        
        $store_id = $failure_order->store_id;
        $store_name = Stores::select('name')->where('technify_store_id', $store_id)->first();   
        $cities = array_map('strtolower', include('cities/'.$store_id.'.php')); 
        
        $cities = array_filter($cities, function($v, $k){
            return strlen($v) > 3;
        }, ARRAY_FILTER_USE_BOTH);  
        sort($cities);
        $cities = ['' => 'please select..', 'karachi' => 'karachi'] + array_combine($cities, $cities);        
        
        return view('orderfailure.edit', compact('failure_order', 'store_name', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {   
        $this->validate($request, ['failure_address' => 'required',  'failure_city' => 'required', 'telephone' => 'required', 'email' => 'required|email']);
        $order_failure = Orderfailure::findorfail($id);    
        $order_failure->update($request->all());        
        $request['id'] = $id;

        if( $request->update_proceed) {
            $response = $this->proceed($request); 
            return redirect('/order/failure')->with('flash_message', 'Order updated and proceed successfully!');
        }        

        return redirect('/order/failure')->with('flash_message', 'Order updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    // failure order proceed
    public function proceed(Request $request)   
    {   
        if($request->id){
            $failure_order_ids = [$request->id];
        } else {
            $failure_order_ids = array_slice(explode(',', implode(',', $request->all())), 1);  
        }    
        $s_c = $f_c = 0; 
        
        foreach ($failure_order_ids as $key => $order_id) {
            
            $get_failure_order = Orderfailure::where('id', $order_id)->first();            
            $address = $get_failure_order->failure_address;
            $city = $get_failure_order->failure_city;
            $telephone = $get_failure_order->telephone;
            $email = $get_failure_order->email;
            $json = json_decode($get_failure_order->orderinfo);

            $json->payload = $json->datapacket;
            unset($json->datapacket);
            
            $json->payload->shipping_address->address = $address;
            $json->payload->shipping_address->city = $city;            
            $json->payload->customer->telephone = $telephone;                        
            $json->payload->customer->email = $email;                        
            $url = Config::get('urls.curl_urls.curl_api_shipped_request');
            
            $useragent = 'Send request order failure';

            $payload = ['action' => 'shipped', 'timestamp' => Carbon::now()->toDateTimeString(), 'dataPacket' => $json->payload]; 
            
            $response = json_decode(Helper::curlRequestWithBasicAuth($url, $payload, $this->username, $this->password), true);
            
            if( $response['statusCode'] == 200 ){
                
                $update_faliure_order = Orderfailure::find($order_id);
                $data = ['status' => '1', 'orderinfo' => json_encode($json->payload)];                
                $update_faliure_order->update($data);
                $s_c+=1;

            } else {
                $f_c+=1;
            }            
        }

        if( $s_c > 0 && $f_c > 0 ) {
            return redirect('order/failure')->with('error_message', 'Orders Few submited and some still errors please check email');
        } else if ( $s_c > 0 ) {
            return redirect('order/failure')->with('flash_message', 'Orders successfully placed');
        } else {
            return redirect('order/failure')->with('error_message', 'Still errors with your orders');
        }

    }

    public function changeOrderFailureStatus(Request $request)
    {
        if($request->id){
            $chnge_status_order_ids = [$request->id];
        } else {
            $chnge_status_order_ids = array_slice(explode(',', implode(',', $request->all())), 1);  
        }    

        foreach ($chnge_status_order_ids as $key => $order_id) {    
            $chnge_status = Orderfailure::where('id', $order_id)->first();
            ($chnge_status) ? $chnge_status->update(['status' => 1]) : ''; 
        }

        return redirect('order/failure')->with('flash_message', 'Order status changed/remove successfully');
    }

}
