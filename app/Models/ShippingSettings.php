<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingSettings extends Model
{
    protected $table = 'shipping_settings';    
    protected $fillable = [
    	'store_id',
    	'order_status',
    	'short_desc'
    ];
}
