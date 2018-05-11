<?php

namespace App\Models\Api\Models;

use Illuminate\Database\Eloquent\Model;

class Orderproduct extends Model
{
    protected $table = 'oc_order_product';

    protected $fillablr = [    		
		'order_product_id',
		'order_id',
		'product_id',
		'name',
		'model',
		'quantity',
		'price',
		'total',
		'tax',
		'reward',
    ];
}
