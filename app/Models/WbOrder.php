<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WbOrder extends Model
{
    protected $table = 'wb_orders';
    protected $guarded = [];
    public $timestamps = false;
}