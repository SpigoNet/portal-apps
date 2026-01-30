<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Importador de Prompts
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                <h3 class="text-2xl font-bold mb-4">Cole seu Prompt Bruto</h3>
                <p class="text-gray-500 mb-6">
                    Cole qualquer texto de IA (Midjourney, Stable Diffusion) abaixo.
                    O sistema irá identificar padrões, sugerir variáveis dinâmicas e vincular a um tema.
                </p>

                <form action="{{ route('mundos-de-mim.admin.importador.analyze') }}" method="POST">
                    @csrf
                    <textarea name="raw_prompt" rows="8" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm p-4" placeholder="Ex: A futuristic photo of a woman with blonde hair and blue eyes standing next to her cyborg dog..." required></textarea>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded shadow flex items-center gap-2">
                            <span>⚡</span> Processar Inteligente
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
