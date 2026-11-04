<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPatient extends Model
{
    protected $table = 'customer_patients';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
