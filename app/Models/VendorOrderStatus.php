<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorOrderStatus extends Model
{
   	protected $table = 'vendor_order_statuses';
    protected $fillable = [ 
    	'id',
        'store_id',
        'order_status_id',
    	'order_status',
        'title',
        'status',
    ];

    // for exclude coloumn in query
    public function scopeExclude( $query, $value = array() ) 
    {
        return $query->select( array_diff($this->fillable, (array) $value) );
    }
}
