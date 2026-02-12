<x-DspaceForms::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Gerenciar Templates de E-mail DSpace') }}
                <span class="text-sm text-gray-500">({{ $config->name }})</span>
            </h2>
            <a href="{{ route('dspace-forms.index') }}">
                <x-secondary-button>
                    <i class="fa-solid fa-house mr-2"></i> {{ __('Início') }}
                </x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-auth-session-status class="mb-4" :status="session('success')" />
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Edite o conteúdo dessas templates de e-mail. O novo conteúdo será incluído na pasta `emails/` ao exportar as configurações no arquivo ZIP.
                    </p>

                    @if ($templates->isEmpty())
                        <p class="text-center text-gray-500 dark:text-gray-400">Nenhuma template de e-mail cadastrada para esta configuração.</p>
                    @else
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($templates as $template)
                                <li class="py-4 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-lg text-indigo-600 dark:text-indigo-400">{{ $template->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $template->description ?? 'Template de e-mail DSpace' }}</div>
                                        @if($template->subject)
                                            <div class="text-sm text-gray-400 dark:text-gray-500 mt-1">Assunto: <em>{{ $template->subject }}</em></div>
                                        @endif
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('dspace-forms.emails.edit', $template) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                            <i class="fa-solid fa-pencil mr-1"></i> Editar Conteúdo
                                        </a>
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
