{{-- Self-hosted Katalog-Fonts (OFL, kein CDN). @font-face lädt die Datei erst,
     wenn die Schrift tatsächlich gerendert wird → 17 zu deklarieren ist günstig. --}}
@php $catalogFonts = config('brands.fonts', []); @endphp
<style>
@foreach($catalogFonts as $f)
@if(!empty($f['variable']))
@font-face{font-family:'{{ $f['family'] }}';font-style:normal;font-display:swap;font-weight:100 900;src:url('{{ route('brands.font', $f['key'].'-wght.woff2') }}') format('woff2');}
@else
@foreach($f['weights'] as $w)
@font-face{font-family:'{{ $f['family'] }}';font-style:normal;font-display:swap;font-weight:{{ $w }};src:url('{{ route('brands.font', $f['key'].'-'.$w.'.woff2') }}') format('woff2');}
@endforeach
@endif
@endforeach
</style>
