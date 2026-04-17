<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = ['id', 'name']; 
    public $incrementing = false;
}
