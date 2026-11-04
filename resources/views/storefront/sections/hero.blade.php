
<section class="hero" aria-label="Featured promotions">
    <div class="hero-main" role="region" aria-roledescription="carousel" aria-label="Promotional banners">
        <div class="hero-viewport swiper" id="hero-slider">
            <div class="hero-track swiper-wrapper" id="hero-track" aria-live="off">
                @php
$slides = [
                    ['tone' => 'tone-a', 'cfg' => 'hero_1', 'defaults' => [
                        'kicker' => 'LIMITED TIME', 'title' => "20% OFF\nSUPPLEMENTS",
                        'subtitle' => 'Top brands, everyday essentials and more.', 'body' => 'OCT26SUPP1',
                        'link_url' => 'category.php?cat=supplements', 'link_label' => 'Get 20% off first order',
                        'image_url' => 'https://images.pexels.com/photos/29060401/pexels-photo-29060401.jpeg?auto=compress&cs=tinysrgb&w=560&h=560&fit=crop',
                        'image_url_2' => 'https://images.pexels.com/photos/13779114/pexels-photo-13779114.jpeg?auto=compress&cs=tinysrgb&w=320&h=320&fit=crop',
                        'badge' => '20%<small>OFF</small>'],
                    ],
                    ['tone' => 'tone-b', 'cfg' => 'hero_2', 'defaults' => [
                        'kicker' => 'LIMITED TIME', 'title' => "UP TO 50% OFF\nTOP DEALS",
                        'subtitle' => 'Big-name savings on the brands you love.', 'body' => 'DEAL50',
                        'link_url' => 'deals', 'link_label' => 'Shop the top deals',
                        'image_url' => 'https://images.pexels.com/photos/15897776/pexels-photo-15897776.jpeg?auto=compress&cs=tinysrgb&w=560&h=560&fit=crop',
                        'image_url_2' => 'https://images.pexels.com/photos/29060398/pexels-photo-29060398.jpeg?auto=compress&cs=tinysrgb&w=320&h=320&fit=crop',
                        'badge' => '50%<small>OFF</small>'],
                    ],
                    ['tone' => 'tone-c', 'cfg' => 'hero_3', 'defaults' => [
                        'kicker' => 'NEW & NOTEWORTHY', 'title' => "GLOW FROM\nWITHIN",
                        'subtitle' => 'Collagen, biotin and marine beauty bestsellers.', 'body' => 'GLOW15',
                        'link_url' => 'category.php?cat=beauty', 'link_label' => 'Shop beauty picks',
                        'image_url' => 'https://images.pexels.com/photos/3762879/pexels-photo-3762879.jpeg?auto=compress&cs=tinysrgb&w=560&h=560&fit=crop',
                        'image_url_2' => 'https://images.pexels.com/photos/4465124/pexels-photo-4465124.jpeg?auto=compress&cs=tinysrgb&w=320&h=320&fit=crop',
                        'badge' => 'NEW<small>ARRIVALS</small>'],
                    ],
                ];
@endphp
@foreach ($slides as $i => $s)
@php
$d = $s['defaults'];
                    $c = storefront_content($s['cfg'], ['kicker' => $d['kicker'], 'title' => $d['title'], 'subtitle' => $d['subtitle'], 'body' => $d['body'], 'link_url' => $d['link_url'], 'link_label' => $d['link_label'], 'image_url' => $d['image_url'], 'image_url_2' => $d['image_url_2']]);
@endphp                <div class="hero-slide swiper-slide {{ $s['tone'] }}" role="group" aria-roledescription="slide" aria-label="Slide {!! $i + 1 !!} of {!! count($slides) !!}">
                    <div class="hero-txt">
                        <span class="hlabel">{{ $c['kicker'] }}</span>
                        <h1>{!! nl2br(e($c['title'])) !!}</h1>
                        <p>{{ $c['subtitle'] }}</p>
                        <div class="hcode">Use Code: <b>{{ $c['body'] }}</b></div>
                        <a class="btn-green" href="{{ $c['link_url'] }}">{{ $c['link_label'] }} {!! ic('arrow', 'ic-arrow-sm') !!}</a>
                    </div>
                    <div class="hero-art">
                        <img class="ha1" src="{{ $c['image_url'] }}" alt="{{ $c['kicker'] }}" width="238" height="238"
                             {!! $i === 0 ? 'fetchpriority="high" loading="eager"' : 'loading="lazy"' !!} decoding="async">
                        <img class="ha2" src="{{ $c['image_url_2'] }}" alt="" width="124" height="124"
                             {!! $i === 0 ? 'loading="eager"' : 'loading="lazy"' !!} decoding="async">
                        <div class="hbadge">{!! $d['badge'] !!}</div>
                    </div>
                </div>
                @endforeach            </div>
        </div>
        <button type="button" class="hero-arrow prev" id="hero-prev" aria-label="Previous slide" aria-controls="hero-track"><span aria-hidden="true">‹</span></button>
        <button type="button" class="hero-arrow next" id="hero-next" aria-label="Next slide" aria-controls="hero-track"><span aria-hidden="true">›</span></button>
        <div class="hero-dots" id="hero-dots">
            @foreach ($slides as $i => $s)                <button type="button" class="hero-dot{!! $i === 0 ? ' on' : '' !!}" data-slide="{!! $i !!}" aria-label="Go to slide {!! $i + 1 !!}"{!! $i === 0 ? ' aria-current="true"' : '' !!}></button>
            @endforeach        </div>
        <div class="hero-count" id="hero-count" aria-hidden="true">1 / {!! count($slides) !!}</div>
    </div>

    <div class="hero-side">
        <a class="mini" href="deals">
            <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?w=560&q=80&auto=format&fit=crop" alt="Top deals">
            <div class="mini-tx"><h3>Up to 50% Off Top Deals</h3><p>Big savings on the brands you love.</p><span class="mini-a">Shop now →</span></div>
        </a>
        <a class="mini" href="#promise">
            <img src="https://images.pexels.com/photos/4498136/pexels-photo-4498136.jpeg?auto=compress&cs=tinysrgb&w=560&h=420&fit=crop" alt="Quality promise">
            <div class="mini-tx"><h3>Our Promise of Quality</h3><p>Authentic products, verified and stored with care.</p><span class="mini-a">Explore →</span></div>
        </a>
        <a class="mini light" href="category.php?cat=supplements">
            <img src="https://images.pexels.com/photos/29060398/pexels-photo-29060398.jpeg?auto=compress&cs=tinysrgb&w=400&h=400&fit=crop" alt="NMN">
            <div class="mini-tx"><h3>NMN for Cellular Health</h3><p>The anti-aging powerhouse in healthy aging.</p><span class="mini-a">Shop now →</span></div>
        </a>
        <a class="mini" href="category.php?cat=supplements">
            <img src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=560&q=80&auto=format&fit=crop" alt="Blood sugar support">
            <div class="mini-tx"><h3>Blood Sugar Support</h3><p>Daily support for balanced glucose levels.</p><span class="mini-a">Shop now →</span></div>
        </a>
    </div>
</section>
