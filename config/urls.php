<?php

return [

    'curl_urls' => [
        'curl_api_shipped' => env('CURL_API_SHIPPED'),
        'curl_api_shipped_request' => env('CURL_API_SHIPPED_REQUEST'),
        'curl_api_shipped_cancel' => env('CURL_API_SHIPPED_CANCEL'),
        'shipping_endpoint' => env('SHIPPING_ENDPOINT'),        
    ],

    'app_urls' => [
        'app_url' => env('APP_URL'),
        'nifi_endpoint' => env('NIFI_ENDPOINT'),
        'end_point' => env('ENDPOINT'),
    ],    

    'navigation_urls' => [
        'home' => env('NAV_HOME'),
        'technify' => env('NAV_TECHNIFY'),
        'store' => env('NAV_STORE'),
        'order_failure' => env('NAV_ORDER_FAILURE'),
        'facebook' => env('NAV_FACEBOOK'),
        'twitter' => env('NAV_TWITTER'),
        'linkdin' => env('NAV_LINKDIN'),
        'technify_dashboard' => env('TECHNIFY_DASHBOARD'),
    ],

    'spec_urls' => [
        'store_spec' => env('SPEC_STORE'),
    ],

    'cities_urls'=>[
        'store_city'=>env('CITYLIST_STORES'),
    ],

    'courier_urls' => [
        'kangaroo' => env('URL_KANGAROO'),
        'kangaroo_cacnel' => env('URL_KANGAROO_CANCEL'),
        'kangaroo_track_order' => env('URL_KANGAROO_TRACK_ORDER'),
        'leopard' => env('URL_LEOPARD'),
        'leopard_city_list' => env('URL_LEOPARD_CITY_LIST'),
        'leopard_track_parcel' => env('URL_LEOPARD_TRACK_PARCEL'),
        'call_courier' => env('URL_CALL_COURIER'),
        'call_courier_city_list' => env('URL_CALL_COURIER_CITY_LIST'),
        'call_courier_track' => env('CALL_COURIER_TRACK'),
        'tcs' => env('URL_TCS'),
        'tcs_track' => env('URL_TCS_TRACK'),
        'shippment_cancel_tcs' => env('URL_SHIPPMENT_CANCEL_TCS'),
        'blueex' => env('URL_BLUEEX'),
        'bluex_track' => env('BLUEX_TRACK'),
        'stallion' => env('STALLION'),
        'fedex' => env('URL_FEDEX'),
        'npm' => env('URL_NPM'),
    ],
];
