<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `customers` (see database/migrations). */
class Customer extends Model
{
    protected $table = 'customers';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function patients()
    {
        return $this->hasMany(CustomerPatient::class, 'customer_id');
    }

}
