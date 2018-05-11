<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    protected $table = 'order_status_logs';
    protected $fillable = [
    	'store_id',
    	'order_id',
    	'order_status_id'  	
    ];
}
