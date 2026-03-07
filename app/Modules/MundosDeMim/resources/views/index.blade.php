<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mundos de Mim - Painel de Controle') }}
        </h2>
    </x-slot>

    
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap');
    
    .mundos-container {
        font-family: 'Inter', sans-serif;
    }
    .mundos-heading {
        font-family: 'Space Grotesk', sans-serif;
    }
    .mundos-bg {
        background-color: #F8F9FA;
    }
    .dark .mundos-bg {
        background-color: #1E1E24;
    }
</style>

<div class="mundos-container mundos-bg min-h-screen">

<div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-[#7B2CBF] rounded-lg shadow-xl p-6 mb-8 text-white">
                <h3 class="text-2xl font-bold">Bem-vindo ao seu estúdio de criação</h3>
                <p class="mt-2 text-[#F8F9FA]">
                    Aqui você define como a Inteligência Artificial enxerga você e seus entes queridos para criar obras
                    de arte diárias.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <a href="{{ route('mundos-de-mim.perfil.index') }}" class="group block">
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200 h-full border-l-4 {{ $stats['biometria_ok'] ? 'border-[#62A87C]' : 'border-orange-500' }}">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900 group-hover:text-[#7B2CBF] transition-colors">
                                    Meu Perfil
                                </h4>
                                <span class="text-2xl">👤</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">
                                Defina sua altura, foto de referência e características físicas.
                            </p>
                            <div class="flex items-center text-sm">
                                @if($stats['biometria_ok'])
                                    <span class="text-[#62A87C] font-semibold text-xs bg-[#62A87C]/10 px-2 py-1 rounded">
                                        ✓ Configurado
                                    </span>
                                @else
                                    <span class="text-orange-600 font-semibold text-xs bg-orange-50 px-2 py-1 rounded">
                                        ⚠ Pendente
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('mundos-de-mim.pessoas.index') }}" class="group block">
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200 h-full border-l-4 border-[#7B2CBF]">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900 group-hover:text-[#7B2CBF] transition-colors">
                                    Entes Queridos
                                </h4>
                                <span class="text-2xl">👥</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">
                                Gerencie quem aparece com você nas fotos em dupla.
                            </p>
                            <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded inline-block">
                                <strong>{{ $stats['pessoas_ativas'] }}</strong> ativos para IA
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('mundos-de-mim.galeria.index') }}" class="group block">
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200 h-full border-l-4 border-[#0077B6]">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900 group-hover:text-[#0077B6] transition-colors">
                                    Minha Galeria
                                </h4>
                                <span class="text-2xl">🖼️</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">
                                Visualize, baixe e compartilhe suas obras de arte diárias.
                            </p>
                            <div class="text-xs text-[#0077B6] bg-[#0077B6]/10 p-2 rounded inline-block font-semibold">
                                {{ $stats['total_artes'] }} artes geradas
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('mundos-de-mim.estilos.index') }}" class="group block">
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200 h-full border-l-4 border-[#3B9AB2]">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900 group-hover:text-[#3B9AB2] transition-colors">
                                    Estilos & Temas
                                </h4>
                                <span class="text-2xl">🎨</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">
                                Explore os universos disponíveis e veja o calendário sazonal.
                            </p>
                            @if($stats['temas_sazonais'] > 0)
                                <div
                                    class="text-xs text-yellow-800 bg-yellow-100 p-2 rounded inline-block font-bold animate-pulse">
                                    ★ {{ $stats['temas_sazonais'] }} evento(s) ativo(s) hoje!
                                </div>
                            @else
                                <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded inline-block">
                                    Explore a coleção permanente
                                </div>
                            @endif
                        </div>
                    </div>
                </a>

            </div>

            <div class="mt-8 border-t pt-6 text-center text-sm text-gray-500">
                <p>Status do Sistema: <span class="text-[#62A87C]">● Operacional</span></p>
                <p class="mt-1 text-xs">As gerações ocorrem diariamente às 07:00 via Job Scheduler.</p>
            </div>
        </div>
    </div>
</div>
</x-MundosDeMim::layout>
