<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `seller_logs` (see database/migrations). */
class SellerLog extends Model
{
    protected $table = 'seller_logs';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'id');
    }
}
