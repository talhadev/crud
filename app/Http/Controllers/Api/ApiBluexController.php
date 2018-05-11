<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Helper;
use Config;

class ApiBluexController extends Controller
{
    // Parsal shipped to BLUE EX 
    public function bluex($payload) {
            	
      	file_put_contents('bluex.json', json_encode($payload, true), true);   

      	$bluex = $payload;
      	$store_id = $bluex['datapacket']['orderinfo']['store_id'];
      	$order_total_price = Helper::getOrderTotalAmount($bluex['datapacket']['total']);
      	$address = $bluex['datapacket']['shipping_address']['address'].', '.$bluex['datapacket']['shipping_address']['address_2'];
      	$city = $bluex['ifCityIsNull'];
      	$order_id = $bluex['datapacket']['orderinfo']['order_id'];      
      	$productDetails = $bluex['datapacket']['cart'];            
      	$products = Helper::productDetails($productDetails); 
      	$weight = ($bluex['datapacket']['weight'] && $bluex['datapacket']['weight'] >= 0.5) ? $bluex['datapacket']['weight'] : 0.5;
      	$get_store_spec = json_decode(Helper::getStoreSpec($store_id), true);     
  		    	
      	set_time_limit(0);
      	$url = Config::get('urls.courier_urls.blueex');
      	$xml ="<?xml version='1.0' encoding='utf-8'?>
      	<BenefitDocument>
        	<AccessRequest>
        	<DocumentType>1</DocumentType>
        	<TestTransaction></TestTransaction>
            	<ShipmentDetail>
                	<ShipperName>'".$get_store_spec['store_info']['name']."'</ShipperName>
                	<ShipperAddress>'". $get_store_spec['store_info']['address'] ."'</ShipperAddress>
                	<ShipperContact>". $get_store_spec['store_info']['phone'] ."</ShipperContact>
                	<ShipperEmail>". $get_store_spec['store_info']['email'] ."</ShipperEmail>
                	<ConsigneeName>". $bluex['datapacket']['customer']['firstname']." ".$bluex['datapacket']['customer']['lastname'] ."</ConsigneeName>
                	<ConsigneeAddress>". $address ."</ConsigneeAddress>
                	<ConsigneeContact>". $bluex['datapacket']['customer']['telephone'] ."</ConsigneeContact>
                	<ConsigneeEmail>". $bluex['datapacket']['customer']['email'] ."</ConsigneeEmail>
                	<CollectionRequired>Y</CollectionRequired>
                	<ProductDetail>Products: ".$products['name']."-----QTY: ".$products['qty'] ."</ProductDetail>
                	<ProductValue>". $order_total_price ."</ProductValue>
                	<OriginCity>". $bluex['origincity'] ."</OriginCity>
                	<DestinationCountry>PK</DestinationCountry>
                	<DestinationCity>LHE</DestinationCity>
                	<ServiceCode>BG</ServiceCode>
                	<ParcelType>N</ParcelType>
                	<Peices>". $products['qty'] ."</Peices>
                	<Weight>". $weight ."</Weight>
                	<Fragile>N</Fragile>
                	<ShipperReference>". $order_id ."</ShipperReference>
                	<InsuranceRequire>N</InsuranceRequire>
                	<InsuranceValue>0</InsuranceValue>
                	<ShipperComment>none</ShipperComment>
            	</ShipmentDetail>
        	</AccessRequest>
      	</BenefitDocument>";

      	$curl = curl_init();
      	curl_setopt($curl, CURLOPT_URL, $url );
      	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
      	curl_setopt($curl, CURLOPT_USERPWD, 'companion:123456');
      	curl_setopt($curl, CURLOPT_POST, 1 );
      	curl_setopt($curl, CURLOPT_POSTFIELDS, array('xml'=>$xml) );
      	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type=application/soap+xml', 'charset=utf-8'));
      	$result = curl_exec ($curl);
      	dd($result);

    }

    // cancel parsal 
    public function cancelShipmentBluex($payload)
    {
        $response = ['response' => false, 'errorMessage' => 'Bluex Cancel Api not integrated at Technify'];

        return $response;
    }

    // track parsal
    public function bluexTrackParsal($payload)
    {
        $response = ['response' => false, 'errorMessage' => 'Bluex Track Parsal Api not integrated at Technify'];

        return $response;
    }
}
