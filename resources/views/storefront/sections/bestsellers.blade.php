
<section class="sec" id="bestsellers">
    {!! secHead('Best Sellers', 'Top rated', 'bestsellers') !!}
    <div class="tabs" data-tabs="bs">
        @foreach ($sets['tabs'] as $i => [$c, $lbl])            <button class="tab{!! $i === 0 ? ' on' : '' !!}" data-cat="{{ $c }}">{{ $lbl }}</button>
        @endforeach    </div>
    <div class="carousel">
        @foreach ($sets['tabs'] as $i => [$c, $lbl])            <div class="track tabpanel" data-tabpanel="{{ $c }}"{!! ($i === 0 ? '' : ' hidden') !!}>
                @foreach (($sets['byCat'][$c] ?? []) as $p)                    <div class="pcard-link">{!! pcard($p) !!}</div>
                @endforeach            </div>
        @endforeach        <button class="arrow prev" data-dir="-1" aria-label="Scroll left">‹</button>
        <button class="arrow next" data-dir="1" aria-label="Scroll right">›</button>
    </div>
</section>
