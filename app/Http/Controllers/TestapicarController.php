<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Testapicar;

class TestapicarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        $cars = Testapicar::all();
        return Response()->json($cars);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $car = Testapicar::create($request->all());
        return response()->json($car);
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
        $car  = Testapicar::find($id);
        if(isset($car)) {
            Testapicar::update($request->all());
        }
        return response()->json($car);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $car  = Testapicar::find($id);
        $car->delete();
        return response()->json('Removed successfully.');
    }

    public function leopardcitylist()
    {
          $curl_handle = curl_init();
          curl_setopt($curl_handle, CURLOPT_URL, 'http://www.leopardscod.com/webservice/getAllCitiesTest/format/json/'); // Write here Test or Production Link
          curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($curl_handle, CURLOPT_POST, 1);
          curl_setopt($curl_handle, CURLOPT_POSTFIELDS, array(
              'api_key' => 'BC8A745A2B7DA612EFA7E26B96E8E829',
              'api_password' => 'A?(.>H5WL2MF9GU'
          ));

          $buffer = curl_exec($curl_handle);    
          curl_close($curl_handle);
          $json = json_decode($buffer, true);     
          $file = 'citylist.txt';
          $str = '';
          foreach ($json['city_list'] as $id => $name) {
            $str .= $name['name']."<br/>";             
          }      
          file_put_contents($file, $str);
    }
}
