<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Table `sellers` (see database/migrations). */
class Seller extends Authenticatable implements MustVerifyEmailContract
{
    use MustVerifyEmailTrait;
    use Notifiable;

    protected $table = 'sellers';

    public $timestamps = false;

    protected $authPasswordName = 'password_hash';

    protected $guarded = [];

    protected $hidden = ['password_hash', 'payout_account'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'rejected_at' => 'datetime',
            'suspended_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'payout_verified' => 'boolean',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function payoutRequests()
    {
        return $this->hasMany(PayoutRequest::class, 'seller_id', 'id');
    }

    public function sellerLedger()
    {
        return $this->hasMany(SellerLedger::class, 'seller_id', 'id');
    }

    public function sellerOrderItems()
    {
        return $this->hasMany(SellerOrderItem::class, 'seller_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id', 'id');
    }

    public function sellerLogs()
    {
        return $this->hasMany(SellerLog::class, 'seller_id', 'id');
    }

    public function sellerDocuments()
    {
        return $this->hasMany(SellerDocument::class, 'seller_id', 'id');
    }
}
