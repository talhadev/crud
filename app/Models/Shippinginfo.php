<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shippinginfo extends Model
{
    protected $table = 'shippinginfo';

    public $timestamps = false;
    
    protected $fillable = [
    	'order_id',
    	'invoice_no',
    	'invoice_prefix',
    	'store_id',
    	'store_name',
    	'store_url',
    	'currency_code',
    	'currency_id',
    	'currency_value',
    	'date_added',
    	'date_modified',
    ];
}
