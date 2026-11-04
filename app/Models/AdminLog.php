<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `admin_logs` (see database/migrations). */
class AdminLog extends Model
{
    protected $table = 'admin_logs';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
