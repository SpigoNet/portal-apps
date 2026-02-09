<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Vincular Professor à Disciplina
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('ant.admin.professores.store') }}" method="POST">
                    @csrf

                    @if($errors->any())
                        <div class="mb-4 bg-red-50 text-red-700 p-3 rounded text-sm">
                            <ul class="list-disc pl-4">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                        <input type="text" name="semestre" value="{{ $semestreAtual }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-bold bg-gray-50">
                    </div>

                    <div x-data="{ tipo: 'existente' }">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Método de Vínculo</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="tipo_vinculo" value="existente" x-model="tipo"
                                        class="form-radio text-indigo-600">
                                    <span class="ml-2">Selecionar Existente</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="tipo_vinculo" value="novo" x-model="tipo"
                                        class="form-radio text-indigo-600">
                                    <span class="ml-2">Cadastrar Novo Professor</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4" x-show="tipo === 'existente'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Selecione o Professor
                                (Usuário)</label>
                            <select name="user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Selecione...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">O usuário deve estar cadastrado no sistema.</p>
                        </div>

                        <div class="mb-4 border-l-4 border-indigo-200 pl-4 py-2 bg-gray-50 rounded"
                            x-show="tipo === 'novo'" style="display: none;">
                            <h3 class="font-bold text-indigo-800 mb-2">Novo Cadastro</h3>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Nome Completo</label>
                                <input type="text" name="new_name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">E-mail</label>
                                <input type="email" name="new_email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Senha</label>
                                <input type="password" name="new_password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Selecione a Matéria</label>
                        <select name="materia_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione...</option>
                            @foreach($materias as $materia)
                                <option value="{{ $materia->id }}">{{ $materia->nome }} ({{ $materia->nome_curto }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <a href="{{ route('ant.admin.professores.index') }}"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            Confirmar Vínculo
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-ANT::layout>