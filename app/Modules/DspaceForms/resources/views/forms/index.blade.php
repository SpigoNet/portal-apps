<x-DspaceForms::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Gerenciar Formulários DSpace') }}
                <span class="text-sm text-gray-500">({{ $config->name }})</span>
            </h2>
            <div class="flex space-x-2">
                {{-- Botão para voltar ao Início do Módulo --}}
                <a href="{{ route('dspace-forms.index') }}">
                    <x-secondary-button>
                        <i class="fa-solid fa-house mr-2"></i> {{ __('Início') }}
                    </x-secondary-button>
                </a>

                <x-primary-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'create-form')"
                    class="bg-green-600 hover:bg-green-500 active:bg-green-700"
                >
                    <i class="fa-solid fa-plus mr-2"></i> Criar Novo Formulário DSpace
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-auth-session-status class="mb-4" :status="session('success')" />

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if ($forms->isEmpty())
                        <p class="text-center text-gray-500 dark:text-gray-400">Nenhum formulário encontrado. Crie um novo para começar.</p>
                    @else
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($forms as $form)
                                <li class="py-4 flex justify-between items-center">
                                    <div class="font-semibold text-lg">{{ $form->name }}</div>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('dspace-forms.forms.edit', $form) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            Editar Estrutura
                                        </a>
                                        <form method="POST" action="{{ route('dspace-forms.forms.destroy', $form) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Tem certeza que deseja excluir o formulário \'{{ $form->name }}\'?')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-DspaceForms::layout>
