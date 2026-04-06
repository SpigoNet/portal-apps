<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 px-4 sm:px-0">
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    Novo Job — ComfyQueue
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Cadastre o workflow JSON e os modelos que o worker Colab deve baixar antes de executar.
                </p>
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

                    <form action="{{ route('comfy-queue.store') }}" method="POST" x-data="jobForm()">
                        @csrf

                        {{-- Tipo / identificador --}}
                        <div class="mb-6">
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Identificador do Workflow
                            </label>
                            <input type="text" x-model="type" name="type" id="type" required
                                placeholder="ex: flux_dev_txt2img"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Nome livre para identificar este job.</p>
                        </div>

                        {{-- Workflow JSON --}}
                        <div class="mb-6">
                            <label for="params" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Workflow JSON <span class="text-gray-400 font-normal">(formato API do ComfyUI)</span>
                            </label>
                            <textarea name="params" id="params" rows="14" x-model="paramsData" required
                                placeholder='Cole aqui o workflow no formato API do ComfyUI (objeto com nós numerados)'
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs"></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                No ComfyUI, use <em>Save (API format)</em> para exportar o workflow nesse formato.
                                O Colab submeterá esse JSON diretamente ao endpoint <code>/prompt</code>.
                            </p>
                        </div>

                        {{-- Modelos necessários --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Modelos necessários
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                Liste os modelos que devem ser baixados antes da execução. O worker verificará se já existem antes de baixar.
                            </p>

                            <div class="space-y-3">
                                <template x-for="(model, idx) in models" :key="idx">
                                    <div class="flex gap-2 items-start p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-2">
                                            <div>
                                                <label class="text-xs text-gray-500 dark:text-gray-400">Nome do arquivo</label>
                                                <input type="text" x-model="model.name"
                                                    placeholder="flux_dev.safetensors"
                                                    class="mt-1 block w-full rounded text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-500 dark:text-gray-400">Destino (pasta no ComfyUI)</label>
                                                <input type="text" x-model="model.dest"
                                                    placeholder="models/checkpoints"
                                                    class="mt-1 block w-full rounded text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-500 dark:text-gray-400">URL de download</label>
                                                <input type="url" x-model="model.url"
                                                    placeholder="https://huggingface.co/..."
                                                    class="mt-1 block w-full rounded text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                        <button type="button" @click="removeModel(idx)"
                                            class="mt-5 text-red-400 hover:text-red-600 transition text-sm px-2 py-1">✕</button>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="addModel"
                                class="mt-3 text-sm px-4 py-2 border border-dashed border-gray-400 dark:border-gray-600 rounded hover:border-indigo-500 text-gray-600 dark:text-gray-400 hover:text-indigo-600 transition">
                                + Adicionar modelo
                            </button>

                            {{-- hidden field com JSON serializado --}}
                            <input type="hidden" name="required_models" :value="JSON.stringify(models.filter(m => m.name || m.url))">
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <a href="{{ route('comfy-queue.index') }}" class="px-4 py-2 border rounded text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Cancelar</a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Criar Job</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('jobForm', () => ({
                type: "{!! old('type', '') !!}",
                paramsData: `{!! old('params', '') !!}`,
                models: {!! old('required_models') ? json_encode(json_decode(old('required_models'))) : '[]' !!},

                addModel() {
                    this.models.push({ name: '', dest: 'models/checkpoints', url: '' });
                },
                removeModel(idx) {
                    this.models.splice(idx, 1);
                },
            }))
        })
    </script>
</x-app-layout>
