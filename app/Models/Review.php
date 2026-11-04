<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `reviews` (see database/migrations). */
class Review extends Model
{
    protected $table = 'reviews';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
