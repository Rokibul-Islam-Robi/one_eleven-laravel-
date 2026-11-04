<?php

namespace App\Support;

/** Storefront navigation data (ported from includes/bootstrap.php). */
class Navigation
{
    public static function categories(): array
    {
        return [
        ['label' => 'Supplements', 'slug' => 'supplements', 'icon' => 'pill', 'tone' => 'g',
         'blurb' => 'Vitamins, herbs and everyday essentials',
         'subs' => [
            ['Vitamins & Minerals', 'daily-vitamins'],
            ['Immune Support', 'immune-support'],
            ['Herbs & Botanicals', 'herbs-botanicals'],
            ['Omega & Fish Oil', 'omega-dha'],
            ['Digestive Support', 'digestive-support'],
            ['Amino Acids', 'amino-acids'],
            ['Bone & Joint', 'bone-joint'],
            ['Sleep & Stress', 'sleep-stress'],
            ['Heart & Cardiovascular', 'heart-health'],
            ['Weight Management', 'weight-management'],
            ['Protein & Performance', 'plant-protein'],
            ['Kids Vitamins', 'kids-nutrition'],
         ]],
        ['label' => 'Beauty', 'slug' => 'beauty', 'icon' => 'sparkle', 'tone' => 'p',
         'blurb' => 'K-beauty, skincare and sun care',
         'subs' => [
            ['Skincare', 'skincare'],
            ['K-Beauty', 'k-beauty'],
            ['Sun Care', 'sun-care'],
            ['Makeup', 'makeup'],
            ['Serums & Oils', 'serums-oils'],
            ['Face Masks', 'face-masks'],
            ['Beauty Tools', 'beauty-tools'],
            ['Fragrance', 'fragrance'],
         ]],
        ['label' => 'Sports Nutrition', 'slug' => 'sports', 'icon' => 'dumbbell', 'tone' => 'b',
         'blurb' => 'Protein, creatine and recovery',
         'subs' => [
            ['Plant Protein', 'plant-protein'],
            ['Pre-Workout', 'pre-workout'],
            ['Creatine', 'creatine'],
            ['Recovery & Glutamine', 'recovery-glutamine'],
            ['Amino Acids', 'amino-acids'],
            ['BCAAs & EAAs', 'bcaas'],
            ['Endurance & Energy', 'endurance'],
            ['Weight Management', 'weight-management'],
         ]],
        ['label' => 'Grocery', 'slug' => 'grocery', 'icon' => 'basket', 'tone' => 'o',
         'blurb' => 'Pantry staples and superfoods',
         'subs' => [
            ['Seeds & Greens', 'seeds-greens'],
            ['Oats & Cereal', 'oats'],
            ['Cooking Oils', 'cooking-oils'],
            ['Snacks', 'snacks'],
            ['Beverages', 'beverages'],
            ['Superfoods', 'superfoods'],
            ['Pantry Staples', 'pantry'],
            ['Honey & Sweeteners', 'sweeteners'],
         ]],
        ['label' => 'Bath & Personal Care', 'slug' => 'bath', 'icon' => 'bottle', 'tone' => 't',
         'blurb' => 'Hair, body and oral care',
         'subs' => [
            ['Shampoo & Conditioner', 'shampoo-conditioner'],
            ['Body Wash', 'body-wash'],
            ['Oral Care', 'toothpaste'],
            ['Hair Treatments', 'hair-treatments'],
            ['Hand Wash', 'hand-wash'],
            ['Deodorants', 'deodorants'],
            ['Shaving Care', 'shaving'],
            ['Soap Bars', 'soap-bars'],
         ]],
        ['label' => 'Home', 'slug' => 'home', 'icon' => 'home', 'tone' => 'v',
         'blurb' => 'Cleaning, laundry and home fragrance',
         'subs' => [
            ['All-Purpose Cleaners', 'all-purpose'],
            ['Laundry', 'laundry'],
            ['Dishwashing', 'dishwashing'],
            ['Bathroom Cleaners', 'bathroom-cleaners'],
            ['Air Care', 'air-care'],
            ['Essential Oils', 'essential-oils'],
            ['Diffusers', 'diffusers'],
            ['Kitchen Storage', 'kitchen-storage'],
            ['Water Bottles', 'water-bottles'],
            ['Dish Tools', 'dish-tools'],
         ]],
        ['label' => 'Baby & Kids', 'slug' => 'kids', 'icon' => 'sparkle', 'tone' => 'g',
         'blurb' => 'Nutrition and care for little ones',
         'subs' => [
            ['Kids Vitamins', 'kids-nutrition'],
            ['Baby Bath & Skin', 'baby-bath'],
            ['Feeding', 'feeding'],
            ['Diapers & Wipes', 'diapers-wipes'],
            ['Baby Care', 'baby-care'],
            ['Kids Snacks', 'kids-snacks'],
         ]],
        ['label' => 'Pets', 'slug' => 'pets', 'icon' => 'heart', 'tone' => 'o',
         'blurb' => 'Supplements and care for pets',
         'subs' => [
            ['Pet Vitamins', 'pet-vitamins'],
            ['Pet Probiotics', 'pet-probiotics'],
            ['Flea & Tick', 'flea-tick'],
            ['Grooming', 'pet-grooming'],
            ['Treats', 'pet-treats'],
            ['Joint Care', 'pet-joint'],
         ]],
        ];
    }

    /** slug => human label for sub-category listing pages. */
    public static function subLabels(): array
    {
        $o = [];
        foreach (self::categories() as $cat) {
            foreach ($cat['subs'] as [$label, $slug]) {
                $o[$slug] = $label;
            }
        }
        return $o;
    }

    /** slug => parent category slug. */
    public static function subParent(): array
    {
        $o = [];
        foreach (self::categories() as $cat) {
            foreach ($cat['subs'] as [, $slug]) {
                $o[$slug] = $cat['slug'];
            }
        }
        return $o;
    }

    /** Category slug => display label. */
    public static function catLabels(): array
    {
        $o = [];
        foreach (self::categories() as $cat) {
            $o[$cat['slug']] = $cat['label'];
        }
        return $o;
    }

    public static function primary(): array
    {
        return ['supplements', 'beauty', 'sports', 'grocery', 'bath', 'home'];
    }

    public static function groups(): array
    {
        return [
        'supplements' => [
            ['Vitamins',          ['daily-vitamins', 'omega-dha', 'bone-joint', 'kids-nutrition']],
            ['Herbal Supplements', ['herbs-botanicals', 'immune-support', 'digestive-support', 'amino-acids']],
            ['Wellness Goals',    ['sleep-stress', 'heart-health', 'weight-management', 'plant-protein']],
        ],
        'beauty' => [
            ['Skincare',        ['skincare', 'serums-oils', 'face-masks']],
            ['Makeup & Sun',    ['makeup', 'sun-care']],
            ['K-Beauty & Tools', ['k-beauty', 'beauty-tools', 'fragrance']],
        ],
        'sports' => [
            ['Protein & Performance', ['plant-protein', 'pre-workout', 'creatine']],
            ['Recovery & Endurance',  ['recovery-glutamine', 'bcaas', 'endurance', 'amino-acids']],
            ['Weight Management',     ['weight-management']],
        ],
        'grocery' => [
            ['Pantry Staples',  ['pantry', 'oats', 'cooking-oils']],
            ['Superfoods',      ['superfoods', 'seeds-greens', 'sweeteners']],
            ['Snacks & Drinks', ['snacks', 'beverages']],
        ],
        'bath' => [
            ['Hair Care',      ['shampoo-conditioner', 'hair-treatments']],
            ['Body & Oral',    ['body-wash', 'toothpaste', 'soap-bars']],
            ['Everyday Care',  ['hand-wash', 'deodorants', 'shaving']],
        ],
        'home' => [
            ['Cleaning',       ['all-purpose', 'bathroom-cleaners', 'dishwashing']],
            ['Laundry & Air',  ['laundry', 'air-care']],
            ['Home & Kitchen', ['essential-oils', 'diffusers', 'kitchen-storage', 'water-bottles', 'dish-tools']],
        ],
        'kids' => [
            ['Nutrition',          ['kids-nutrition', 'kids-snacks']],
            ['Baby Care',          ['baby-bath', 'baby-care']],
            ['Feeding & Diapers',  ['feeding', 'diapers-wipes']],
        ],
        'pets' => [
            ['Supplements',       ['pet-vitamins', 'pet-probiotics', 'pet-joint']],
            ['Care & Grooming',   ['flea-tick', 'pet-grooming']],
            ['Treats',            ['pet-treats']],
        ],
        ];
    }

    /** "Trending Brands" rail ranked by total review volume. */
    public static function trendingBrands(): array
    {
        $score = [];
        foreach (Catalog::all() as $p) {
            $b = trim((string) ($p['b'] ?? ''));
            if ($b === '') {
                continue;
            }
            if (! isset($score[$b])) {
                $score[$b] = ['name' => $b, 'reviews' => 0, 'count' => 0];
            }
            $score[$b]['reviews'] += (int) ($p['rc'] ?? 0);
            $score[$b]['count']++;
        }
        usort($score, fn ($a, $b) => $b['reviews'] <=> $a['reviews']);
        $out = [];
        foreach (array_slice($score, 0, 10) as $b) {
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($b['name']))) ?? '', '-');
            $b['slug'] = $slug !== '' ? $slug : 'brand';
            $out[] = $b;
        }
        return $out;
    }

    public static function regions(): array
    {
        return [
        ['code' => 'BD', 'label' => 'BD · Bangladesh',      'currency' => 'BDT'],
        ['code' => 'IN', 'label' => 'IN · India',           'currency' => 'INR'],
        ['code' => 'US', 'label' => 'US · United States',   'currency' => 'USD'],
        ['code' => 'GB', 'label' => 'GB · United Kingdom',  'currency' => 'GBP'],
        ['code' => 'AE', 'label' => 'AE · UAE',             'currency' => 'AED'],
        ['code' => 'EU', 'label' => 'EU · Europe',          'currency' => 'EUR'],
        ];
    }

    public static function menus(): array
    {
        return [
        'deals'  => ['label' => 'Deals', 'href' => 'deals', 'icon' => 'tag', 'items' => [
            ['All Deals', 'deals'],
            ['Best Sellers', 'bestsellers'],
            ['New Arrivals', 'new'],
            ['Flash Deals', 'deals#flash'],
            ['Clearance Picks', 'deals'],
        ]],
        'topics' => ['label' => 'Health Topics', 'href' => 'category.php?cat=supplements', 'icon' => 'leaf', 'items' => [
            ['Immune Health', 'category.php?cat=immune-support'],
            ['Digestion & Gut Health', 'category.php?cat=digestive-support'],
            ['Heart & Cardiovascular', 'category.php?cat=heart-health'],
            ['Energy & Focus', 'category.php?cat=daily-vitamins'],
            ['Sleep & Stress', 'category.php?cat=sleep-stress'],
            ['Beauty From Within', 'category.php?cat=beauty'],
            ['Sports & Recovery', 'category.php?cat=sports'],
            ['Healthy Aging', 'category.php?cat=bone-joint'],
        ]],
        ];
    }

    /** Unique alphabetical brand names. */
    public static function brands(): array
    {
        $b = [];
        foreach (Catalog::all() as $p) {
            $n = trim((string) ($p['b'] ?? ''));
            if ($n !== '') {
                $b[$n] = true;
            }
        }
        $b = array_keys($b);
        sort($b);
        return $b;
    }

    public static function currencies(): array
    {
        return [
        ['code' => 'BDT', 'label' => 'BDT ৳', 'rate' => 1.0,    'locale' => 'en-US', 'symbol' => '৳'],
        ['code' => 'USD', 'label' => 'USD $', 'rate' => 0.0084, 'locale' => 'en-US', 'symbol' => '$'],
        ['code' => 'EUR', 'label' => 'EUR €', 'rate' => 0.0077, 'locale' => 'de-DE', 'symbol' => '€'],
        ['code' => 'GBP', 'label' => 'GBP £', 'rate' => 0.0065, 'locale' => 'en-GB', 'symbol' => '£'],
        ['code' => 'INR', 'label' => 'INR ₹', 'rate' => 0.070,  'locale' => 'en-IN', 'symbol' => '₹'],
        ['code' => 'AED', 'label' => 'AED د.إ', 'rate' => 0.031, 'locale' => 'en-AE', 'symbol' => 'د.إ'],
        ];
    }

    public static function languages(): array
    {
        return [['code' => 'EN', 'label' => 'English'], ['code' => 'BN', 'label' => 'বাংলা']];
    }
}
