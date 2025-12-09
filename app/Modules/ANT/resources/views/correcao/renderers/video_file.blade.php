<div class="w-full h-full flex flex-col items-center justify-center bg-gray-900 p-4">
    <div class="text-white mb-4 text-sm">Visualizando Vídeo Enviado (Player HTML5)</div>

    <video controls class="max-w-full max-h-full rounded shadow object-contain" style="max-height: 80vh;">
        <source src="{{ $data['url'] }}">
        Seu navegador não suporta a tag de vídeo.
    </video>

    <a href="{{ $data['url'] }}" target="_blank" class="text-xs text-indigo-400 hover:text-indigo-200 mt-4 break-all">
        Baixar Arquivo
    </a>
</div>
