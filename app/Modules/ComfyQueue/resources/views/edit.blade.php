<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 px-4 sm:px-0 flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                        Editar Job #{{ $job->id }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Ajuste tipo, status e JSON do workflow/modelos para corrigir e reenfileirar rapidamente.
                    </p>
                </div>
                <a href="{{ route('comfy-queue.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Voltar</a>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if ($errors->any())
                        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-300 rounded p-4">
                            <p class="font-medium text-red-700 dark:text-red-400 mb-2">Corrija os erros abaixo:</p>
                            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-300">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('comfy-queue.update', $job->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                                <input type="text" name="type" id="type" required value="{{ old('type', $job->type) }}"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select name="status" id="status" required
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach(['pending', 'processing', 'done', 'error'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $job->status) === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="prompt_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prompt ID</label>
                                <input type="text" name="prompt_id" id="prompt_id" value="{{ old('prompt_id', $job->prompt_id) }}"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="error" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Erro</label>
                                <input type="text" name="error" id="error" value="{{ old('error', $job->error) }}"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="params" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Workflow JSON (params)
                            </label>
                            <textarea name="params" id="params" rows="14" required
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs">{{ old('params', json_encode($job->params ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        </div>

                        <div class="mb-6">
                            <label for="required_models" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Modelos necessários (JSON)
                            </label>
                            <textarea name="required_models" id="required_models" rows="8"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs">{{ old('required_models', json_encode($job->required_models ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pode ficar vazio ou [] se não houver modelos para download.</p>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <a href="{{ route('comfy-queue.index') }}" class="px-4 py-2 border rounded text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Cancelar</a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Salvar alterações</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
