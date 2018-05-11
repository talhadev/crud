<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $table = 'shippings';
    protected $fillable = [
    	'order_id',
        'store_id',
        'amount',
        'address',
        'city',
    	'email',
    	'orderinfo',    	
    	'courier_name',
        'courier_status',
        'customer_number',
        'payment_method',
    	'order_tracking_id',
    	'status',
        'cities'
    ];
}
