<x-TreeTask::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirmação da IA') }} 🤖
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">O que será feito:</h3>
                    <div class="prose max-w-none text-gray-800 bg-blue-50 p-4 rounded-lg border border-blue-100">
                        {!! $html_preview !!}
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-sm font-bold text-gray-300 mb-2 uppercase">Comando SQL Gerado (Para Verificação)</h3>
                    <pre class="bg-gray-900 text-green-400 p-4 rounded text-xs overflow-x-auto font-mono">{{ $sql_command }}</pre>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <a href="{{ route('treetask.ai.index') }}" class="text-gray-600 hover:text-gray-900 underline">
                    Cancelar / Tentar Novamente
                </a>

                <form action="{{ route('treetask.ai.execute') }}" method="POST">
                    @csrf
                    <input type="hidden" name="sql_command" value="{{ $sql_command }}">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded shadow-lg transform hover:scale-105 transition">
                        Confirmar e Executar ✅
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-TreeTask::layout>
