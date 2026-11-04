<?php

namespace App\Support;

/**
 * Bangladesh administrative districts — 8 divisions, 64 districts.
 * Ported from includes/districts.php (single source of truth for the checkout
 * <select> and the server-side validation).
 */
class Districts
{
    /** @return array<string, string[]> division => districts */
    public static function byDivision(): array
    {
        return [
            'Barishal'   => ['Barguna', 'Barishal', 'Bhola', 'Jhalokati', 'Patuakhali', 'Pirojpur'],
            'Chattogram' => ['Bandarban', 'Brahmanbaria', 'Chandpur', 'Chattogram', "Cox's Bazar", 'Cumilla',
                             'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati'],
            'Dhaka'      => ['Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur', 'Manikganj',
                             'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari', 'Shariatpur', 'Tangail'],
            'Khulna'     => ['Bagerhat', 'Chuadanga', 'Jashore', 'Jhenaidah', 'Khulna', 'Kushtia', 'Magura',
                             'Meherpur', 'Narail', 'Satkhira'],
            'Mymensingh' => ['Jamalpur', 'Mymensingh', 'Netrokona', 'Sherpur'],
            'Rajshahi'   => ['Bogura', 'Chapai Nawabganj', 'Joypurhat', 'Naogaon', 'Natore', 'Pabna', 'Rajshahi', 'Sirajganj'],
            'Rangpur'    => ['Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat', 'Nilphamari', 'Panchagarh', 'Rangpur', 'Thakurgaon'],
            'Sylhet'     => ['Habiganj', 'Moulvibazar', 'Sunamganj', 'Sylhet'],
        ];
    }

    /** Flat list of all 64 district names. */
    public static function all(): array
    {
        return array_merge(...array_values(self::byDivision()));
    }

    /** Canonical district name (case-insensitive) or '' when not one of the 64. */
    public static function canonical(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        foreach (self::all() as $d) {
            if (strcasecmp($d, $value) === 0) {
                return $d;
            }
        }
        return '';
    }

    /** Division a district belongs to ('' when unknown). */
    public static function divisionOf(string $district): string
    {
        foreach (self::byDivision() as $division => $list) {
            if (in_array($district, $list, true)) {
                return $division;
            }
        }
        return '';
    }
}
