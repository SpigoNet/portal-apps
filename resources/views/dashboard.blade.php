<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Portal de Aplicativos') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- INÍCIO: Bloco para exibir mensagens de erro e sucesso -->
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Erro</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Sucesso</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            <!-- FIM: Bloco de mensagens -->

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-8">
                @forelse ($packages as $package)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <!-- Cabeçalho do Pacote com a cor de fundo -->
                        <div class="p-6" style="background-color: {{ $package->bg_color ?? '#f9fafb' }};">
                            <h3 class="text-2xl font-bold tracking-tight text-gray-900">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="font-normal text-gray-700 mt-1">{{ $package->description }}</p>
                            @endif
                        </div>
                        <!-- Corpo do Pacote com os ícones dos Apps -->
                        <div class="p-6 bg-white border-t border-gray-200">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                                @foreach ($package->visible_apps as $app)
                                    <a href="{{ url($app->start_link) }}" class="block p-4 text-center bg-gray-50 border border-gray-200 rounded-lg shadow hover:bg-gray-100 transition-transform transform hover:scale-105">
                                        {{-- Para o ícone funcionar, adicione o Font Awesome ao seu projeto --}}
                                        <i class="{{ $app->icon }} fa-2x mb-2 text-gray-600"></i>
                                        <h5 class="font-bold tracking-tight text-gray-900 text-sm">
                                            {{ $app->title }}
                                        </h5>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <p>Nenhum aplicativo disponível para você no momento.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
