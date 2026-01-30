<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Minha Galeria de Mundos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if($generations->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 text-center">
                    <p class="text-gray-500 text-lg">Você ainda não possui mundos gerados.</p>
                    <p class="text-sm text-gray-400 mt-2">Complete seu perfil biométrico e aguarde a próxima geração diária!</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($generations as $art)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                            <div class="relative aspect-square bg-gray-100">
                                <img src="{{ $art->image_url }}" alt="Arte do dia {{ $art->reference_date->format('d/m/Y') }}"
                                     class="object-cover w-full h-full cursor-pointer hover:opacity-90 transition-opacity">

                                <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded backdrop-blur-sm">
                                    {{ $art->reference_date->format('d/m') }}
                                </div>
                            </div>

                            <div class="p-4">
                                <h3 class="font-bold text-gray-800 text-sm truncate">
                                    {{ $art->theme->name ?? 'Tema Surpresa' }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $art->theme->is_seasonal ? '✨ Edição Especial' : 'Estilo Clássico' }}
                                </p>

                                <div class="mt-3 flex justify-between items-center">
                                    <a href="{{ $art->image_url }}" target="_blank" download class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">
                                        Baixar HD
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $generations->links() }}
                </div>
            @endif
        </div>
    </div>
</x-MundosDeMim::layout>
