<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase;

class FirebaseController extends Controller
{

    public function index() {	
		// Constants
		$firebase_url = "https://smsgateway-41d03.firebaseio.com/";	
		$node_put 	  = "temperature.json";

		$data = array(
		    "order_id" => 1,
		    "order_status_id" => 5,
		    "customer_name" => 'Ahsan',
		);

		// JSON encoded
		$json = json_encode( $data );			
		// Initialize cURL
		$curl = curl_init();
		// Create
		curl_setopt( $curl, CURLOPT_URL, $FIREBASE . $NODE_PUT );
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $json );
		// Read
		// curl_setopt( $curl, CURLOPT_URL, $FIREBASE . $NODE_GET );
		
		// Get return value
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		// Make request
		// Close connection
		$response = curl_exec( $curl );		dd($response);
		curl_close( $curl );
		// Show result
		echo $response . "\n";
    }

    public function testfirebase() {

		require base_path().('/vendor/autoload.php');
		$firebase_url = 'https://smsgateway-41d03.firebaseio.com/';
		$firebase = new \Firebase\FirebaseLib($firebase_url);
		dd($firebase);
		$firebase->getDatabase();
		dd($database->getDatabase());
		$database->getReference('users')
			->set([
					'order_id' => 1,
					'order_status_id' => 5,
					'customer_name' => 'Ahsan'
				]);
		
		$default_path = '/';

		$firebase = new \Firebase\FirebaseLib($default_url);
		dd($firebase);
		/*$k1 = $firebase->get('/k1');
		$k2 = $firebase->get('/20110909/tot');

		echo $k1.'<br>';
		echo $k2.'<br>';*/


		$firebase->set('/k1', 5566);		
		$k1 = $firebase->get('/k1');
		$k2 = $firebase->get('/20110909/tot');

		echo $k1.'<br>';
		echo $k2.'<br>';
    }

}
