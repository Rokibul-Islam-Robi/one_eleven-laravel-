<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `commission_rules` (see database/migrations). */
class CommissionRule extends Model
{
    protected $table = 'commission_rules';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
            'created_at' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }
}
