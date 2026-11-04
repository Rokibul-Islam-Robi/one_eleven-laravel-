<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `orders` (see database/migrations). */
class Order extends Model
{
    protected $table = 'orders';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function doctorReferrals()
    {
        return $this->hasMany(DoctorReferral::class, 'order_id', 'id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    public function courier()
    {
        return $this->belongsTo(CourierMethod::class, 'courier_id', 'id');
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function orderReturns()
    {
        return $this->hasMany(OrderReturn::class, 'order_id', 'id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'order_id', 'id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'order_id', 'id');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'order_id', 'id');
    }

    public function sellerOrderItems()
    {
        return $this->hasMany(SellerOrderItem::class, 'order_id', 'id');
    }
}
