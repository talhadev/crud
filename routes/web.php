<?php
use App\Jobs\TestQueue;
use App\Jobs\SendSuccessFailure;
use App\Jobs\SendOrderFailure;
use App\Jobs\SendNotifyStore;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    return view('welcome');
});

// clear cache,view and config
Route::get('/all-clear',function(){
    Artisan::call('cache:clear');
    // Artisan::call('optimize');
    Artisan::call('view:clear');
    Artisan::call('config:cache');
    return "<h1>Cache is Cleared</h1>";
});

Route::get('shippingprocess', 'ShippingController@index');
Route::get('kangaroo', 'ShippingController@kangaroo');
Route::get('tcs', 'ShippingController@tcs');
Route::get('leopard', 'ShippingController@leopard');
Route::get('bluex', 'ShippingController@bluex');
Route::get('fedex', 'ShippingController@fedex');
Route::get('update', 'testController@UpdateCourierStatus');
Route::post('insert', 'testController@insert');

// FIREBASE
Route::get('firebase', 'FirebaseController@index');
Route::get('testfirebase', 'FirebaseController@testfirebase');

Route::get('testapi/{action}/{timestamp}/{entity}/{id}/{shipping}', 'ApiController@api');

Route::group(['prefix' => 'api', 'testapicar' => 'App\Http\Controllers'], function($app) {	

	$app->post('testapicar', 'TestapicarController@store');
 
	$app->put('testapicar/{id}', 'TestapicarController@update');
 	 
	$app->delete('testapicar/{id}', 'TestapicarController@destory');
 
	$app->get('testapicar', 'TestapicarController@index');

});

Route::get('check/version', function(){
    return phpversion();
});

Route::get('create/store', function () {
    return App\models\stores::create([        
        'store_name' => 'Ego Store',
        'address' => 'Abc Road Xyz Street',
        'store_url' => 'https://wearego.com/',
        'uuid' => uniqid('', true)
    ]);
});

Route::get('uuid', function(){
    return uniqid('', true);
});

Route::resource('store', 'StoresController');
Route::get('order/success', 'OrderfailureController@index');
Route::get('order/cancelled', 'OrderfailureController@index');

Route::resource('order/failure', 'OrderfailureController');

Route::post('order/failure/proceed', 'OrderfailureController@proceed');
Route::post('order/failure/change/status', 'OrderfailureController@changeOrderFailureStatus');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('test/middleware', ['middleware' => 'admin', function(){
    return 'This page may only viewed bu Admins';
}]);

Route::get('administration/only', function(){
    return view('role.administration');
});

// action 
Route::resource('actions', 'ActionController');
Route::post('action/filter', 'ActionController@index');

Route::get('stores/spec', 'StoresController@storeSpec');
Route::get('test1', 'testController@test');

// test send background email
Route::get('test/email', function(){

    $data = ['name' => 'Test Queue', 'Test' => 'Test'];    
    /*$data = ['name' => 'Store', 'store_url' => 'store_url', 'password' => 'password', 'email' => 'email', 'support_email' => 'support_email', 'uuid' => 'uuid', 'created_at' => 'created_at', 'dashboard_url' => Config::get('urls.app_urls.app_url')];    */
    $email = 'ahsans895@gmail.com';    
    dispatch(new TestQueue($data, $email));    
    
    /*Mail::send('emails.test_queue', $data, function($message) use($email) {      
        $message->to($email)->subject('Test Queue!');
    });*/
});

Route::get('check/email/view/ordersuccess', function() {
    $order_id = 123; $courier_company = 'courier company'; $tracking_id = 1111; $courier_url = 'https://technify.pk'; 
    return view('emails.ordersuccess', compact('order_id', 'courier_company', 'tracking_id', 'courier_url'));
});

Route::get('check/email/view/orderfailure', function() {
    $order_id = 123; $app_url = 'https://technify.pk';  
    return view('emails.orderfailure', compact('order_id', 'app_url'));
});

Route::get('store/pull/order/status/{license_key}', 'StoresController@pullOrderStatus');

// AJAX call
Route::get('shippingsettings/{store_id}', 'ShippingSettingsController@show');
Route::post('shippingsettings/update', 'ShippingSettingsController@update');

Route::get('shipping/stats', function() {
    
    $stats = App\Models\Shipping::where('amount', null)->get();
    foreach( $stats as $key => $value ) {
        
        if( isset( $value->orderinfo ) ) {
            
            @file_put_contents('file.json', $value->order_id);            
            $orderamount = (int) end(json_decode($value['orderinfo'], true)['datapacket']['total'])['value'];
            $value->update(['amount' => $orderamount]);

        }         
    }

    dd('done');
});