<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Criar Novo Formulário DSpace') }}
            </h2>
            <div class="flex space-x-2">
                {{-- Botão para voltar ao Índice de Formulários --}}
                <a href="{{ route('dspace-forms.forms.index') }}">
                    <x-secondary-button>
                        <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('Voltar à Lista') }}
                    </x-secondary-button>
                </a>

                {{-- Botão para voltar ao Início do Módulo --}}
                <a href="{{ route('dspace-forms.index') }}">
                    <x-secondary-button>
                        <i class="fa-solid fa-house mr-2"></i> {{ __('Início') }}
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('dspace-forms.forms.store') }}">
                        @csrf

                        {{-- Passa uma instância vazia para a parcial --}}
                        @include('DspaceForms::forms._form', ['form' => new \App\Modules\DspaceForms\Models\DspaceForm()])

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button>
                                {{ __('Salvar Formulário') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
