<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerDocument extends Model
{
    protected $table = 'seller_documents';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'id');
    }
}
