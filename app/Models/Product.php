<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\Category;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model

{
        use SoftDeletes;

    protected $fillable = ['name', 'description', 'price', 'stock','is_active','deleted_at','category_id','image'];


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }


    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

}
