<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `payout_requests` (see database/migrations). */
class PayoutRequest extends Model
{
    protected $table = 'payout_requests';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'id');
    }
}
