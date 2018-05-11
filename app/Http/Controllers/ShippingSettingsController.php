<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// MODELS
use App\Models\ShippingSettings;

class ShippingSettingsController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($store_id)
    {                
        $shipping_settings = ShippingSettings::where(compact('store_id'))->first();        
        if($shipping_settings){
            $response = true;
            return compact('response', 'shipping_settings');
        } else {
            $response = false;
            return compact('response');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $rules = [            
            'store_id' => 'required',
            'order_status' => 'required'
        ];
        $input = $request->all();
        $validator = \Validator::make($input, $rules);

        if($validator->fails()) {
            $response = ['response' => false, 'errorMessage' => $validator->errors()->all()[0]];
        } else {        
            $store_id = $input['store_id'];
            $shipping_setting = ShippingSettings::where(compact('store_id'))->first();
            if($shipping_setting) {
                $shipping_setting->update($input);
            } else {
                ShippingSettings::create($input);
            }
            $response = ['response' => true, 'successMessage' => 'shipping settings updated'];
        }

        return $response;
    }
}
