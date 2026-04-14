<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    Editar Modelo de Job
                </h2>
                <a href="{{ route('comfy-queue.job-models.index') }}" class="text-sm text-indigo-600 hover:underline">
                    ← Voltar
                </a>
            </div>

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded p-4">
                    <ul class="list-disc list-inside text-sm text-red-600">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('comfy-queue.job-models.update', $modelo->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6">
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">
                            Nome <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nome" id="nome" required
                            class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            value="{{ $modelo->nome }}">
                    </div>

                    <div class="mb-6">
                        <label for="json" class="block text-sm font-medium text-gray-700 mb-1">
                            JSON <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-2">
                            Use <code>__nome_variavel__</code> para criar campos dinâmicos que serão solicitados ao criar o job.
                        </p>
                        <textarea name="json" id="json" rows="20" required
                            class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-xs">{{ json_encode($modelo->json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <a href="{{ route('comfy-queue.job-models.index') }}" 
                           class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">
                            Cancelar
                        </a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            Atualizar Modelo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>