<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `settings` (see database/migrations). */
class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'skey';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];
}
