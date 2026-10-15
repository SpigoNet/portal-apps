@props(['tamanho' => 34, 'texto' => true, 'fonte' => '22px'])
<span {{ $attributes->merge(['style' => 'display:inline-flex;align-items:center;gap:10px']) }}>
    <svg width="{{ $tamanho }}" height="{{ $tamanho }}" viewBox="0 0 48 48" role="img" aria-label="Mundos de Mim">
        <circle cx="35" cy="12" r="6" fill="#FF7A59"/>
        <path d="M6 41V28a9 9 0 0 1 18 0v13M24 41V28a9 9 0 0 1 18 0v13" fill="none" stroke="#A79BFF" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    @if($texto)
        <span class="mm-display" style="font-size:{{ $fonte }};font-weight:700">mundos de mim</span>
    @endif
</span>
