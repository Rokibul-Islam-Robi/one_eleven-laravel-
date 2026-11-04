<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `seller_ledger` (see database/migrations). */
class SellerLedger extends Model
{
    protected $table = 'seller_ledger';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'id');
    }
}
