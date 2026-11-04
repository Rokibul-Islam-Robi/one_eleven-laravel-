<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

/** Courier methods + shipping/COD fee calculation. Ported from includes/checkout.php. */
class CourierService
{
    /** @return array<int, array> active couriers ordered like the original (sort_order, fee). */
    public static function active(): array
    {
        try {
            return DB::table('courier_methods')->where('status', 'active')
                ->orderBy('sort_order')->orderBy('fee')->get()
                ->map(fn ($r) => (array) $r)->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function default(): ?array
    {
        try {
            $c = DB::table('courier_methods')->where('status', 'active')->where('is_default', 1)->first();
            if ($c) {
                return (array) $c;
            }
            $c = DB::table('courier_methods')->where('status', 'active')
                ->orderBy('sort_order')->orderBy('fee')->first();
            return $c ? (array) $c : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function byId(int $id): ?array
    {
        try {
            $c = DB::table('courier_methods')->where('id', $id)->where('status', 'active')->first();
            return $c ? (array) $c : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /** Resolve an active courier id by its code (e.g. "redx" -> id); 0 when unknown. */
    public static function resolveId(string $code): int
    {
        if ($code === '') {
            return 0;
        }
        try {
            return (int) (DB::table('courier_methods')->where('code', $code)->where('status', 'active')->value('id') ?: 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    /** Shipping + COD fee for a subtotal against a courier row. */
    public static function quote(array $courier, float $subtotal, string $paymentMethod): array
    {
        $fee = (float) ($courier['fee'] ?? 60);
        $freeMin = (float) ($courier['free_delivery_min'] ?? 0);
        if ($freeMin > 0 && $subtotal >= $freeMin) {
            $fee = 0.0;
        }
        $codFee = 0.0;
        if ($paymentMethod === 'cod') {
            $codFee = (float) ($courier['cod_fee'] ?? 0);
        }
        return ['shipping' => $fee, 'cod_fee' => $codFee];
    }
}
