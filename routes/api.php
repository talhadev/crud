<?php

use Illuminate\Http\Request;
 
/*  Ahsan 
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('apitest/order', 'ApiController@api');

Route::post('technifystore', 'ApiController@technifystore');

Route::post('readjson/pushtodb', 'ApiController@readJsonPushToDB');

Route::post('readspec', 'ApiController@readspec');

// get location or weight and shipped 
Route::post('shipped', 'Api\vendor\VendorController@index');

// get location or weight and shipped 
Route::post('readtoship', 'Api\vendor\VendorController@readToShip');

// datapacket shipped to courier
Route::post('shipped/courier', 'ShippingController@shipped');

// for TCS
Route::post('shipped/tcs', 'ShippingController@tcs');
Route::post('shipped/cancel/tcs', 'ShippingController@cancelShipmentTcs');
Route::post('shipped/trackorder/tcs', 'ShippingController@trackTcsOrder');

// for kangaroo
Route::post('shipped/kangaroo', 'ShippingController@kangaroo');
Route::post('shipped/cancel/kangaroo', 'ShippingController@cancelShipmentKangaroo');

// for leopard
Route::post('shipped/leopard', 'ShippingController@leopard');
// for call courier
Route::post('shipped/callCourier', 'ShippingController@callCourier');
// for fedex
Route::post('shipped/fedex', 'ShippingController@fedex');
// for blue ex
Route::post('shipped/bluex', 'ShippingController@bluex');

Route::post('leopard/cities', 'TestapicarController@leopardcitylist');

// cancel shipments
Route::post('shipped/cancel', 'ShippingController@cancelShipment');

// get license key
Route::post('store/license/key', 'StoresController@getLicense');
Route::post('authorize/key', 'StoresController@authorizeKey');

Route::post('store/order/status', 'StoresController@orderStatus');

Route::middleware('admin:api')->get('test/middleware', function(){
    return 'This page may only viewed by Admin';
});

// End point
Route::post('shipping/endpoint', 'Api\ApiActionController@action');

// version 2 End point
Route::post('shipping/v2', 'Api\V2\ActionController@action');

Route::post('update/order/status','Connector\MagentorestController@abc');
Route::post('update/order/status/magento/soap','Connector\MagentosoapController@abc');

######################################################

// Shehriyar nadeem Routes
Route::post('shipping','Api\vendor\ShippingVendorController@actions');
