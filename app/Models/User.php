<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Table `users` (staff/admin accounts; see database/migrations). */
class User extends Authenticatable
{
    protected $table = 'users';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    /** The original schema stores the bcrypt hash in `password_hash`. */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }
}
