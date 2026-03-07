<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight font-heading">
            {{ __('Painel de Controle') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Banner de Boas Vindas com o Roxo Tech -->
            <div class="bg-[#7B2CBF] rounded-[2rem] shadow-xl p-8 mb-10 text-white relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <h3 class="text-3xl font-bold font-heading mb-2">Bem-vindo ao seu estúdio de criação</h3>
                <p class="text-[#F8F9FA]/80 text-lg max-w-2xl">
                    Aqui você define como a Inteligência Artificial enxerga você e seus entes queridos para criar obras de arte diárias impressionantes.
                </p>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Perfil: Verde Aquarela -->
                <a href="{{ route('mundos-de-mim.perfil.index') }}" class="group block">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl hover:shadow-xl transition-all duration-300 transform group-hover:-translate-y-1 h-full border-l-4 {{ $stats['biometria_ok'] ? 'border-[#62A87C]' : 'border-orange-500' }}">
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xl font-bold text-gray-900 group-hover:text-[#62A87C] transition-colors font-heading">
                                    Meu Perfil
                                </h4>
                                <div class="w-12 h-12 bg-[#62A87C]/10 rounded-xl flex items-center justify-center text-[#62A87C] text-xl group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-user-astronaut"></i>
                                </div>
                            </div>
                            <p class="text-gray-600 mb-6">
                                Defina sua altura, foto de referência e características físicas.
                            </p>
                            <div class="flex items-center text-sm mt-auto">
                                @if($stats['biometria_ok'])
                                    <span class="text-[#62A87C] font-semibold bg-[#62A87C]/10 px-3 py-1.5 rounded-lg flex items-center gap-2">
                                        <i class="fa-solid fa-circle-check"></i> Configurado
                                    </span>
                                @else
                                    <span class="text-orange-600 font-semibold bg-orange-50 px-3 py-1.5 rounded-lg flex items-center gap-2">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Pendente
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Entes Queridos: Roxo Tech -->
                <a href="{{ route('mundos-de-mim.pessoas.index') }}" class="group block">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl hover:shadow-xl transition-all duration-300 transform group-hover:-translate-y-1 h-full border-l-4 border-[#7B2CBF]">
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xl font-bold text-gray-900 group-hover:text-[#7B2CBF] transition-colors font-heading">
                                    Entes Queridos
                                </h4>
                                <div class="w-12 h-12 bg-[#7B2CBF]/10 rounded-xl flex items-center justify-center text-[#7B2CBF] text-xl group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                            <p class="text-gray-600 mb-6">
                                Gerencie quem aparece com você nas fotos em dupla ou família.
                            </p>
                            <div class="text-sm text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg inline-block font-medium">
                                <span class="text-[#7B2CBF] font-bold">{{ $stats['pessoas_ativas'] }}</span> perfis ativos para IA
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Galeria: Azul Metálico -->
                <a href="{{ route('mundos-de-mim.galeria.index') }}" class="group block">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl hover:shadow-xl transition-all duration-300 transform group-hover:-translate-y-1 h-full border-l-4 border-[#0077B6]">
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xl font-bold text-gray-900 group-hover:text-[#0077B6] transition-colors font-heading">
                                    Minha Galeria
                                </h4>
                                <div class="w-12 h-12 bg-[#0077B6]/10 rounded-xl flex items-center justify-center text-[#0077B6] text-xl group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-images"></i>
                                </div>
                            </div>
                            <p class="text-gray-600 mb-6">
                                Visualize, baixe e compartilhe suas obras de arte diárias.
                            </p>
                            <div class="text-sm text-[#0077B6] bg-[#0077B6]/10 px-3 py-1.5 rounded-lg inline-block font-semibold flex items-center gap-2 w-max">
                                <i class="fa-solid fa-bolt"></i> {{ $stats['total_artes'] }} artes geradas
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Estilos: Azul Aquarela -->
                <a href="{{ route('mundos-de-mim.estilos.index') }}" class="group block">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl hover:shadow-xl transition-all duration-300 transform group-hover:-translate-y-1 h-full border-l-4 border-[#3B9AB2]">
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xl font-bold text-gray-900 group-hover:text-[#3B9AB2] transition-colors font-heading">
                                    Estilos & Temas
                                </h4>
                                <div class="w-12 h-12 bg-[#3B9AB2]/10 rounded-xl flex items-center justify-center text-[#3B9AB2] text-xl group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-palette"></i>
                                </div>
                            </div>
                            <p class="text-gray-600 mb-6">
                                Explore os universos disponíveis e veja o calendário sazonal.
                            </p>
                            @if($stats['temas_sazonais'] > 0)
                                <div class="text-sm text-yellow-800 bg-yellow-100 px-3 py-1.5 rounded-lg inline-block font-bold animate-pulse flex items-center gap-2 w-max">
                                    <i class="fa-solid fa-star"></i> {{ $stats['temas_sazonais'] }} evento(s) ativo(s) hoje!
                                </div>
                            @else
                                <div class="text-sm text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg inline-block font-medium">
                                    Explore a coleção permanente
                                </div>
                            @endif
                        </div>
                    </div>
                </a>
            </div>

            <div class="mt-12 text-center text-sm text-gray-400 font-medium">
                <p>Status do Sistema: <span class="text-[#62A87C]">● Operacional</span></p>
                <p class="mt-1">As gerações ocorrem diariamente às 07:00 via Job Scheduler.</p>
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
