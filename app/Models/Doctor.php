<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `doctors` (see database/migrations). */
class Doctor extends Model
{
    protected $table = 'doctors';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registration_verified_at' => 'date',
        ];
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'doctor_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function doctorReferrals()
    {
        return $this->hasMany(DoctorReferral::class, 'doctor_id', 'id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'doctor_id', 'id');
    }
}
