<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `brands` (see database/migrations). */
class Brand extends Model
{
    protected $table = 'brands';
    public $timestamps = false;
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'id');
    }
}
