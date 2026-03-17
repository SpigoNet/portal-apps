<div class="w-full h-full flex flex-col bg-gray-900 text-white" x-data="{ showUnity: false }">

    {{-- Toolbar --}}
    <div class="bg-gray-800 px-4 py-2 flex items-center gap-3 text-sm border-b border-gray-700 flex-shrink-0">
        <span class="material-icons text-yellow-400 text-base">folder_zip</span>
        <span class="text-gray-300 font-medium">Conteúdo do ZIP</span>
        <span class="text-gray-500 text-xs">({{ count($data['arquivos_extraidos']) }} arquivo(s))</span>

        @if($data['unity_url'])
            <button @click="showUnity = !showUnity"
                    :class="showUnity ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'"
                    class="ml-auto text-white px-3 py-1 rounded flex items-center gap-1 text-xs font-bold transition-colors">
                <span class="material-icons text-xs" x-text="showUnity ? 'list' : 'play_arrow'">play_arrow</span>
                <span x-text="showUnity ? 'Ver Arquivos' : 'Executar WebGL (Unity)'">Executar WebGL (Unity)</span>
            </button>
        @endif

        <a href="{{ $data['url'] }}" target="_blank"
           class="@if(!$data['unity_url']) ml-auto @endif text-gray-400 hover:text-white flex items-center gap-1 text-xs">
            <span class="material-icons text-xs">download</span>
            Baixar ZIP
        </a>
    </div>

    {{-- File listing (shown by default) --}}
    <div x-show="!showUnity" class="flex-1 overflow-auto p-4">
        @if(empty($data['arquivos_extraidos']))
            <p class="text-gray-500 text-sm italic">Nenhum arquivo encontrado no ZIP.</p>
        @else
            @if($data['unity_url'])
                <div class="mb-3 px-3 py-2 bg-green-900 border border-green-700 rounded text-xs text-green-300 flex items-center gap-2">
                    <span class="material-icons text-sm">info</span>
                    Build Unity WebGL detectada — clique em <strong>Executar WebGL</strong> para rodar no navegador.
                </div>
            @endif

            <div class="font-mono text-sm space-y-0.5">
                @foreach($data['arquivos_extraidos'] as $file)
                    @php
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $depth = substr_count($file, '/');
                        $isHtml = $ext === 'html';
                        $iconColor = match(true) {
                            in_array($ext, ['js', 'ts', 'jsx', 'tsx']) => 'text-yellow-400',
                            in_array($ext, ['css', 'scss', 'less'])    => 'text-blue-400',
                            in_array($ext, ['html', 'htm'])             => 'text-orange-400',
                            in_array($ext, ['png', 'jpg', 'gif', 'webp', 'svg']) => 'text-pink-400',
                            in_array($ext, ['json', 'xml', 'yaml', 'yml']) => 'text-teal-400',
                            default => 'text-gray-400',
                        };
                    @endphp
                    <div class="flex items-center gap-1.5 py-0.5 hover:bg-gray-800 rounded px-2"
                         style="padding-left: {{ ($depth * 16) + 8 }}px">
                        <span class="material-icons text-xs {{ $iconColor }}">insert_drive_file</span>
                        <span class="{{ $isHtml ? 'text-orange-300 font-semibold' : 'text-gray-300' }}">
                            {{ basename($file) }}
                        </span>
                        @if($ext)
                            <span class="text-gray-600 text-xs">.{{ $ext }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Unity WebGL iframe (hidden until activated) --}}
    @if($data['unity_url'])
        <div x-show="showUnity" class="flex-1 bg-black" x-cloak>
            <iframe src="{{ $data['unity_url'] }}"
                    class="w-full h-full border-0"
                    allowfullscreen
                    allow="autoplay; fullscreen"></iframe>
        </div>
    @endif

</div>
