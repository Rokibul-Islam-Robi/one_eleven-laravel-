
<section class="fdb" id="flash">
    <span class="fdb-flame">{!! ic('clock', 'ic-fdb') !!}</span>
    @php
$flashbar = storefront_content('flash_bar', ['title' => 'Flash Deals', 'subtitle' => 'Extra savings on bestsellers — today only']);
@endphp    <div class="fdb-tx"><b>{{ $flashbar['title'] }}</b><span>{{ $flashbar['subtitle'] }}</span></div>
    <div class="fdb-clock">
        <span class="fdb-lbl">Ends in</span>
        <span class="cd-banner"><b id="cd-h">13</b><i>:</i><b id="cd-m">17</b><i>:</i><b id="cd-s">36</b></span>
    </div>
    <a class="fdb-cta" href="deals">Shop deals {!! ic('arrow', 'ic-arrow-sm') !!}</a>
</section>
