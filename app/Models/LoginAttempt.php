<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `login_attempts` (see database/migrations). */
class LoginAttempt extends Model
{
    protected $table = 'login_attempts';
    public $timestamps = false;
    protected $guarded = [];
}
