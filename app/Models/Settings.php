<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'settings';
    protected $fillable = [
        'order_id',
        'store_id',
        'courier_name',
        'credentials',
        'logix',
        'origin_city',
        'est_delivery',
        'status',
        'locations'
        


    ];
}

