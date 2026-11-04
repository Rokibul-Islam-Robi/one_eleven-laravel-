
<section class="sec" id="deals">
    {!! secHead('Deals', 'Bestsellers', 'deals') !!}
    <div class="carousel">
        <div class="track">
            @foreach ($sets['deals'] as $p)                <div class="pcard-link">{!! pcard($p) !!}</div>
            @endforeach        </div>
        <button class="arrow prev" data-dir="-1" aria-label="Scroll left">‹</button>
        <button class="arrow next" data-dir="1" aria-label="Scroll right">›</button>
    </div>
</section>
