
<section class="sec" id="categories">
    <div class="sec-head">
        <div class="sec-t"><h2>Shop by Category</h2></div>
        <a class="sec-a" href="category.php?cat=supplements">Shop All {!! ic('arrow', 'ic-arrow-sm') !!}</a>
    </div>
    <div class="cats-carousel">
        <div class="cats-viewport">
            <div class="cats">
                @foreach ($NAV_CATEGORIES as $cat)                    <a class="cat" href="category.php?cat={{ $cat['slug'] }}">
                        <span class="cat-ico tone-{{ $cat['tone'] }}">{!! ic($cat['icon'], 'ic-cat') !!}</span>
                        <b>{{ $cat['label'] }}</b>
                    </a>
                @endforeach            </div>
        </div>
        <button class="arrow prev" data-dir="-1" aria-label="Scroll left">‹</button>
        <button class="arrow next" data-dir="1" aria-label="Scroll right">›</button>
    </div>
    <div class="pills">
        <a class="pill" href="category.php?cat=daily-vitamins">Vitamins</a>
        <a class="pill" href="category.php?cat=heart-health">Heart</a>
        <a class="pill" href="category.php?cat=immune-support">Immune Support</a>
        <a class="pill" href="category.php?cat=sleep-stress">Mood Balance &amp; Anxiety</a>
        <a class="pill" href="category.php?cat=shampoo-conditioner">Hair Care</a>
        <a class="pill" href="category.php?cat=all-purpose">Cleaners</a>
        <a class="pill" href="category.php?cat=k-beauty">K-Beauty</a>
        <a class="pill" href="category.php?cat=amino-acids">Sexual Health</a>
        <a class="pill" href="category.php?cat=bone-joint">Mineral Supplements</a>
        <a class="pill" href="category.php?cat=plant-protein">Plant Protein</a>
    </div>
</section>
