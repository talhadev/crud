<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orderfailure extends Model
{
    protected $table = 'order_failure';
    protected $fillable = [
    	'order_id',
    	'store_id',
    	'failure_address',
    	'failure_city',
        'telephone',
    	'email',
    	'status',
    	'orderinfo'
	];
}
