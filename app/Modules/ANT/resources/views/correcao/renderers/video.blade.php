<div class="w-full h-full flex flex-col items-center justify-center bg-gray-900 p-4">
    <div class="text-white mb-4 text-sm">Visualizando Conteúdo de Vídeo (iFrame)</div>

    {{-- O iframe tentará carregar o link. Pode funcionar para YouTube/Vimeo se for o link de embed --}}
    <iframe src="{{ $data['url'] }}"
            class="w-full h-full border-0 rounded shadow-lg"
            allowfullscreen
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
    </iframe>

    <a href="{{ $data['url'] }}" target="_blank" class="text-xs text-indigo-400 hover:text-indigo-200 mt-4 break-all">
        Abrir em Nova Aba: {{ $data['url'] }}
    </a>
</div>
