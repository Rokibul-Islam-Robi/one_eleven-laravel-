
<section class="sec" id="trending">
    {!! secHead('Trending Now', 'Most viewed', 'bestsellers') !!}
    <div class="trends">
        <div class="trend tone-g">
            <div class="trend-tx"><h3>Daily Wellness</h3><p>Essentials for your routine.</p><a class="btn-outline" href="category.php?cat=supplements">Shop now</a></div>
            <img src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=480&q=80&auto=format&fit=crop" alt="Daily wellness">
        </div>
        <div class="trend tone-p">
            <div class="trend-tx"><h3>Beauty Favorites</h3><p>Nature-powered skincare.</p><a class="btn-outline" href="category.php?cat=beauty">Explore</a></div>
            <img src="https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=480&q=80&auto=format&fit=crop" alt="Beauty favorites">
        </div>
        <div class="trend tone-b">
            <div class="trend-tx"><h3>Sports Nutrition</h3><p>Fuel your strongest days.</p><a class="btn-outline" href="category.php?cat=sports">Shop now</a></div>
            <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=480&q=80&auto=format&fit=crop" alt="Sports nutrition">
        </div>
    </div>
    <div class="carousel trend-products">
        <div class="track">
            @foreach ($sets['trending'] as $p)                <div class="pcard-link">{!! pcard($p) !!}</div>
            @endforeach        </div>
        <button class="arrow prev" data-dir="-1" aria-label="Scroll left">‹</button>
        <button class="arrow next" data-dir="1" aria-label="Scroll right">›</button>
    </div>
</section>
