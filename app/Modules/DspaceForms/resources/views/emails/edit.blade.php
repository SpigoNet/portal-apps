<x-DspaceForms::layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editando Template: ') . $template->name }}
            </h2>
            <a href="{{ route('dspace-forms.emails.index', ['config_id' => $template->xml_configuration_id]) }}">
                <x-secondary-button>
                    <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('Voltar para Templates') }}
                </x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('dspace-forms.emails.update', $template) }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            {{-- Nome/Arquivo da Template --}}
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Arquivo: `emails/{{ $template->name }}`</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $template->description ?? 'Sem descrição' }}</p>
                            </div>

                            {{-- Campo Assunto (Referência) --}}
                            <div>
                                <x-input-label for="subject" :value="__('Assunto (Referência)')" />
                                <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject', $template->subject)" autocomplete="off" />
                                <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Este campo é opcional e apenas para referência. O assunto real é definido no conteúdo Velocity.
                                </p>
                            </div>

                            {{-- Campo Conteúdo --}}
                            <div>
                                <x-input-label for="content" :value="__('Conteúdo da Template (Velocity/Texto Puro)')" />
                                <textarea id="content" name="content" rows="20" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full font-mono text-sm" required>{{ old('content', $template->content) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('content')" />
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    **ATENÇÃO**: Este campo é sensível à sintaxe Velocity e aos parâmetros (ex: `${params[0]}`). Edite com cuidado.
                                </p>
                            </div>

                            {{-- Campo Descrição --}}
                            <div>
                                <x-input-label for="description" :value="__('Descrição/Notas')" />
                                <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $template->description)" autocomplete="off" />
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button>
                                {{ __('Salvar Template') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-DspaceForms::layout>
