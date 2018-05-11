<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class logs extends Model
{
    protected $table = 'logs';

    protected $fillable = [
        'store_id',
        'order_id',
        'courier_name',
        'status_timeline'

    ];
}
