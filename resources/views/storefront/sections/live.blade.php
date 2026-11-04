@php
/* "bHerb Live" wellness from around the world. */
$liveCards = [
    ['MA', 'Morocco', 'Argan & rose rituals', 26],
    ['US', 'United States', 'Everyday essentials', 3],
    ['SA', 'Saudi Arabia', 'Beauty from within', 4],
    ['FR', 'France', 'Marine beauty secrets', 17],
];
@endphp <section class="live" id="live">
    <div class="live-tx">
        <span class="plabel">bHERB LIVE</span>
        <h2>Wellness from around<br>the world</h2>
        <p>Explore traditions and healthy living inspiration from our global community.</p>
        <a class="btn-green" href="category.php?cat=beauty">Explore bHerb Live</a>
    </div>
    <div class="live-cards">
        @foreach ($liveCards as [$code, $country, $note, $pid])
@php
$p = productById($pid); if (!$p) continue;
@endphp            <div class="live-c">
                <header>
                    <span class="roundel">{{ $code }}</span>
                    <div><b>{{ $country }}</b><small>{{ $note }}</small></div>
                </header>
                <a class="live-p" href="product/{{ $p['id'] }}">
                    <img loading="lazy" src="{{ $p['i'] }}" alt="{{ $p['n'] }}">
                    <div>
                        <h4>{{ $p['n'] }}</h4>
                        <span class="lp" data-price="{!! (int)priceval($p['p']) !!}">{{ $p['p'] }}</span>
                    </div>
                </a>
            </div>
        @endforeach    </div>
</section>
