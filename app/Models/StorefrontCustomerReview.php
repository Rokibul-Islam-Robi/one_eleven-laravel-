<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontCustomerReview extends Model
{
    protected $table = 'storefront_customer_reviews';
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
