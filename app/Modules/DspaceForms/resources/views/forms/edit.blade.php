<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Formulário: ') . $form->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Informações Básicas') }}
                    </h3>
                    <form method="POST" action="{{ route('dspace-forms.forms.update', $form) }}">
                        @csrf
                        @method('PUT')

                        @include('DspaceForms::forms._form', ['form' => $form])

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button>
                                {{ __('Salvar Alterações') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Estrutura do Formulário: Linhas e Campos') }}
                    </h3>

                    <div class="border border-dashed border-gray-600 dark:border-gray-500 p-8 rounded-lg text-center">
                        <p class="text-gray-500 dark:text-gray-400">
                            A próxima grande etapa será implementar aqui a lógica de interface (possivelmente via Livewire) para adicionar, ordenar e configurar as Linhas (`DspaceFormRow`) e os Campos (`DspaceFormField`) dinamicamente.
                        </p>
                        <p class="mt-4">
                            <x-secondary-button disabled>{{ __('Adicionar Linha / Campo (Em Breve)') }}</x-secondary-button>
                        </p>
                    </div>

                    {{-- Futuramente, o loop para renderizar a estrutura atual do formulário virá aqui. --}}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
