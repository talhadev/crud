<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testapicar extends Model
{
	protected $table = 'testapicars';
	
    protected $fillable = [
    	'company', 
    	'name', 
    	'year'
	];	 
}
