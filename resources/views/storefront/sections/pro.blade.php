
<section class="pro" id="pro">
    <div class="pro-b dark">
        <div class="pro-tx">
            <span class="plabel">PROFESSIONAL BRANDS</span>
            <h2>Trusted by professionals.<br>Chosen by you.</h2>
            <p>Discover a curated selection of professional brands known for their focus on quality and purity.</p>
            <ul class="pro-feats">
                <li><span>{!! ic('flask', 'ic-pf') !!}</span><div><b>Rigorous testing</b><small>Every batch reviewed</small></div></li>
                <li><span>{!! ic('shield', 'ic-pf') !!}</span><div><b>cGMP standards</b><small>Made to strict quality controls</small></div></li>
                <li><span>{!! ic('star', 'ic-pf') !!}</span><div><b>Expert partnerships</b><small>Trusted by wellness communities</small></div></li>
            </ul>
            <a class="btn-green" href="category.php?cat=supplements">Shop Professional Brands</a>
        </div>
        <div class="pro-art">
            <img src="https://images.pexels.com/photos/3825527/pexels-photo-3825527.jpeg?auto=compress&cs=tinysrgb&w=900&h=560&fit=crop" alt="Laboratory professionals">
        </div>
    </div>
    <div class="brandlogos" id="brands">
        @php
$brandsIdx = brand_index();
@endphp
@foreach (['THORNE', 'NOW Foods', 'Jarrow Formulas', 'Solgar', 'Life Extension', 'Garden of Life', 'Sports Research', 'Nordic Naturals'] as $brand)
@php
$bSlug = brand_slug($brand);
            $href = isset($brandsIdx[$bSlug]) ? 'brand.php?id=' . $bSlug : 'search?q=' . urlencode($brand);
@endphp            <span><a href="{{ $href }}">{{ $brand }}</a></span>
        @endforeach    </div>
</section>
