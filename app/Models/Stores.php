<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stores extends Model
{    
    protected $table = 'stores';
    
    protected $fillable = [    
        'technify_store_id',
        'platforms',
        'user_id',
        'name',
        'address',
        'store_url',
        'endpoint',
        'uuid',
        'module_active',
        'email',
        'telephone',
        'support_email',
        'password',
        'auth',
    ];
}
