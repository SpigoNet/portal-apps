@if ($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <strong>Opa!</strong> Havia alguns problemas com seus dados.<br><br>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-6">
    <!-- Título -->
    <div>
        <label for="title" class="block font-medium text-sm text-gray-700">Título</label>
        <input id="title" name="title" type="text" value="{{ old('title', $app->title ?? '') }}" required class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
    </div>

    <!-- Descrição -->
    <div>
        <label for="description" class="block font-medium text-sm text-gray-700">Descrição</label>
        <textarea id="description" name="description" rows="3" required class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description', $app->description ?? '') }}</textarea>
    </div>

    <!-- Link Inicial -->
    <div>
        <label for="start_link" class="block font-medium text-sm text-gray-700">Link Inicial (Ex: /todo-app)</label>
        <input id="start_link" name="start_link" type="text" value="{{ old('start_link', $app->start_link ?? '') }}" required class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
    </div>

    <!-- Visibilidade -->
    <div>
        <label for="visibility" class="block font-medium text-sm text-gray-700">Visibilidade</label>
        <select id="visibility" name="visibility" required class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="public" @selected(old('visibility', $app->visibility ?? '') == 'public')>Público (todos veem)</option>
            <option value="private" @selected(old('visibility', $app->visibility ?? '') == 'private')>Privado (logados veem)</option>
            <option value="specific" @selected(old('visibility', $app->visibility ?? '') == 'specific')>Específico (usuários selecionados)</option>
        </select>
    </div>

    <!-- Usuários (para visibilidade específica) -->
    <div id="users-selection" class="{{ old('visibility', $app->visibility ?? '') == 'specific' ? '' : 'hidden' }}">
        <label for="users" class="block font-medium text-sm text-gray-700">Usuários com Acesso</label>
        <select id="users" name="users[]" multiple class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(in_array($user->id, old('users', $app->users->pluck('id')->toArray() ?? [])))>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="flex items-center justify-end mt-6">
    <a href="{{ route('admin.apps.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
        Salvar
    </button>
</div>

<script>
    // Script para mostrar/esconder a seleção de usuários
    document.getElementById('visibility').addEventListener('change', function () {
        const usersSelection = document.getElementById('users-selection');
        if (this.value === 'specific') {
            usersSelection.classList.remove('hidden');
        } else {
            usersSelection.classList.add('hidden');
        }
    });
