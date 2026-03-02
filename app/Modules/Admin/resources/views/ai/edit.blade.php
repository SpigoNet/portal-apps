<x-Admin::layout>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Editar Provedor: {{ $provedor->nome }}
                </h2>
                <a href="{{ route('admin.ai.provedores.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Voltar
                </a>
            </div>
        </div>

        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('admin.ai.provedores.update', $provedor) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        @include('Admin::ai._form')

                        <div class="mt-6 flex items-center gap-4">
                            <x-primary-button>{{ __('Atualizar') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-Admin::layout>
