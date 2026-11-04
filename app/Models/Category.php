<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `categories` (see database/migrations). */
class Category extends Model
{
    protected $table = 'categories';
    public $timestamps = false;
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}
