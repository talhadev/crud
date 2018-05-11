<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $table = 'action';

    protected $fillable = [
    	'controller',
    	'method',
        'call',
        'action'
    ];
}
