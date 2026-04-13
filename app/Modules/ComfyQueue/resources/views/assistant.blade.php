<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 px-4 sm:px-0 flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                        Assistente de Insert — ComfyQueue
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Cole o workflow JSON e altere apenas o prompt de entrada.
                    </p>
                </div>
                <a href="{{ route('comfy-queue.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    ← Voltar
                </a>
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

                    <form action="{{ route('comfy-queue.assistant.store') }}" method="POST" x-data="assistantForm()">
                        @csrf

                        {{-- Prompt Positivo --}}
                        <div class="mb-6">
                            <label for="prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Prompt Positivo <span class="text-red-500">*</span>
                            </label>
                            <textarea x-model="prompt" name="prompt" id="prompt" rows="4" required
                                placeholder="Ex: Chibi anime sticker of Monkey D. Luffy from One Piece..."
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">O texto principal que será usado no nó de prompt positivo.</p>
                        </div>

                        {{-- Prompt Negativo --}}
                        <div class="mb-6">
                            <label for="negative_prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Prompt Negativo
                            </label>
                            <textarea x-model="negativePrompt" name="negative_prompt" id="negative_prompt" rows="2"
                                placeholder="Ex: text, watermark, low quality..."
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">O que evitar na geração. Padrão: "text, watermark"</p>
                        </div>

                        {{-- Workflow JSON --}}
                        <div class="mb-6">
                            <label for="workflow_json" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Workflow JSON <span class="text-red-500">*</span>
                            </label>
                            <textarea x-model="workflowJson" name="workflow_json" id="workflow_json" rows="18" required
                                placeholder='Cole aqui o workflow completo no formato API do ComfyUI'
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs"></textarea>
                            <div class="mt-2 flex gap-2">
                                <button type="button" @click="formatJson" 
                                    class="text-xs px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Formatar JSON
                                </button>
                                <button type="button" @click="clearWorkflow" 
                                    class="text-xs px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Limpar
                                </button>
                            </div>
                        </div>

                        {{-- Preview --}}
                        <div x-show="previewShow" class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview do Workflow</h3>
                            <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-x-auto" x-text="previewJson"></pre>
                        </div>

                        <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="togglePreview" 
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <span x-text="previewShow ? 'Ocultar Preview' : 'Mostrar Preview'"></span>
                            </button>
                            
                            <div class="flex gap-3">
                                <a href="{{ route('comfy-queue.assistant') }}" class="px-4 py-2 border rounded text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    Cancelar
                                </a>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                                    Criar Job
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('assistantForm', () => ({
                prompt: '',
                negativePrompt: 'text, watermark',
                workflowJson: '',
                previewShow: false,

                get previewJson() {
                    if (!this.workflowJson) return '';
                    try {
                        const wf = JSON.parse(this.workflowJson);
                        const updated = this.updateWorkflow(wf, this.prompt, this.negativePrompt);
                        return JSON.stringify(updated, null, 2);
                    } catch (e) {
                        return 'JSON inválido';
                    }
                },

                updateWorkflow(workflow, positive, negative) {
                    for (const key in workflow) {
                        const node = workflow[key];
                        if (node.class_type === 'CLIPTextEncode') {
                            if (node.inputs.text && (node.inputs.text.toLowerCase().includes('positive') || node.inputs.text.length < 200)) {
                                node.inputs.text = positive;
                            }
                        }
                    }
                    if (workflow['7']) {
                        workflow['7'].inputs.text = negative || 'text, watermark';
                    }
                    return workflow;
                },

                togglePreview() {
                    this.previewShow = !this.previewShow;
                },

                formatJson() {
                    try {
                        const obj = JSON.parse(this.workflowJson);
                        this.workflowJson = JSON.stringify(obj, null, 2);
                    } catch (e) {
                        alert('JSON inválido');
                    }
                },

                clearWorkflow() {
                    this.workflowJson = '';
                    this.previewShow = false;
                }
            }))
        })
    </script>
</x-app-layout>