<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    //model relationships ke order_detail menggunakan hasMany
    public function order_detail(){
    	return $this->hasMany(Order_detail::class);
    }

    protected $dates = ['created_at'];

    public function customer(){
    	return $this->belongsTo(Customer::class);
    }

    public function user(){
    	return $this->belongsTo(User::class);
    }
}
