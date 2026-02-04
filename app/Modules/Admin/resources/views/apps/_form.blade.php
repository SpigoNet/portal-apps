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
        <input id="title" name="title" type="text" value="{{ old('title', $app->title ?? '') }}" required
            class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
    </div>

    <!-- Descrição -->
    <div>
        <label for="description" class="block font-medium text-sm text-gray-700">Descrição</label>
        <textarea id="description" name="description" rows="3" required
            class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description', $app->description ?? '') }}</textarea>
    </div>

    <!-- Link Inicial -->
    <div>
        <label for="start_link" class="block font-medium text-sm text-gray-700">Link Inicial (Ex: /todo-app)</label>
        <input id="start_link" name="start_link" type="text" value="{{ old('start_link', $app->start_link ?? '') }}"
            required
            class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
    </div>

    <!-- Visibilidade -->
    <div>
        <label for="visibility" class="block font-medium text-sm text-gray-700">Visibilidade</label>
        <select id="visibility" name="visibility" required
            class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="public" @selected(old('visibility', $app->visibility ?? '') == 'public')>Público (todos veem)
            </option>
            <option value="private" @selected(old('visibility', $app->visibility ?? '') == 'private')>Privado (logados
                veem)</option>
            <option value="specific" @selected(old('visibility', $app->visibility ?? '') == 'specific')>Específico
                (usuários selecionados)</option>
        </select>
    </div>

    <!-- Usuários (para visibilidade específica) -->
    <div id="users-selection" class="{{ old('visibility', $app->visibility ?? '') == 'specific' ? '' : 'hidden' }}">
        <label for="users" class="block font-medium text-sm text-gray-700">Usuários com Acesso</label>
        <select id="users" name="users[]" multiple
            class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(in_array($user->id, old('users', $app->users->pluck('id')->toArray() ?? [])))>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
    </div>
    <!-- PWA Configurações -->
    <div class="col-span-full border-t pt-6 mt-6">
        <h3 class="text-lg font-bold mb-4 text-gray-800">Configurações PWA (Progressive Web App)</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- PWA Short Name -->
            <div>
                <label for="pwa_short_name" class="block font-medium text-sm text-gray-700">Nome Curto (App
                    Mobile)</label>
                <input id="pwa_short_name" name="pwa_short_name" type="text" maxlength="30"
                    value="{{ old('pwa_short_name', $app->pwa_short_name ?? '') }}"
                    class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <p class="text-xs text-gray-500 mt-1">Nome que aparecerá embaixo do ícone na tela do celular.</p>
            </div>

            <!-- PWA Scope -->
            <div>
                <label for="pwa_scope" class="block font-medium text-sm text-gray-700">Escopo (URL Base)</label>
                <input id="pwa_scope" name="pwa_scope" type="text" value="{{ old('pwa_scope', $app->pwa_scope ?? '') }}"
                    class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>

            <!-- PWA Colors -->
            <div>
                <label for="pwa_background_color" class="block font-medium text-sm text-gray-700">Cor de Fundo (Splash
                    Screen)</label>
                <input id="pwa_background_color" name="pwa_background_color" type="color"
                    value="{{ old('pwa_background_color', $app->pwa_background_color ?? '#1a1b26') }}"
                    class="block mt-1 w-full h-10 rounded-md shadow-sm border-gray-300">
            </div>

            <div>
                <label for="pwa_theme_color" class="block font-medium text-sm text-gray-700">Cor do Tema (Status
                    Bar)</label>
                <input id="pwa_theme_color" name="pwa_theme_color" type="color"
                    value="{{ old('pwa_theme_color', $app->pwa_theme_color ?? '#ccf381') }}"
                    class="block mt-1 w-full h-10 rounded-md shadow-sm border-gray-300">
            </div>

            <!-- PWA Display -->
            <div>
                <label for="pwa_display" class="block font-medium text-sm text-gray-700">Modo de Exibição</label>
                <select id="pwa_display" name="pwa_display"
                    class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="standalone" @selected(old('pwa_display', $app->pwa_display ?? '') == 'standalone')>
                        Standalone (App Nativo)</option>
                    <option value="fullscreen" @selected(old('pwa_display', $app->pwa_display ?? '') == 'fullscreen')>
                        Fullscreen (Tela Cheia)</option>
                    <option value="minimal-ui" @selected(old('pwa_display', $app->pwa_display ?? '') == 'minimal-ui')>
                        Minimal UI</option>
                    <option value="browser" @selected(old('pwa_display', $app->pwa_display ?? '') == 'browser')>Browser
                        (Navegador)</option>
                </select>
            </div>

            <!-- PWA Orientation -->
            <div>
                <label for="pwa_orientation" class="block font-medium text-sm text-gray-700">Orientação</label>
                <select id="pwa_orientation" name="pwa_orientation"
                    class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="any" @selected(old('pwa_orientation', $app->pwa_orientation ?? '') == 'any')>Qualquer
                    </option>
                    <option value="portrait" @selected(old('pwa_orientation', $app->pwa_orientation ?? '') == 'portrait')>
                        Retrato (Vertical)</option>
                    <option value="landscape" @selected(old('pwa_orientation', $app->pwa_orientation ?? '') == 'landscape')>Paisagem (Horizontal)</option>
                    <option value="natural" @selected(old('pwa_orientation', $app->pwa_orientation ?? '') == 'natural')>
                        Natural</option>
                </select>
            </div>
            <!-- Icone (Seletor Visual) -->
            <div class="col-span-full">
                <label class="block font-medium text-sm text-gray-700 mb-2">Selecione o Ícone</label>
                <input type="hidden" name="icon" id="iconPathInput" value="{{ old('icon', $app->icon ?? '') }}">

                <div
                    class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-4 p-4 border rounded-md bg-gray-50 max-h-64 overflow-y-auto">
                    @forelse($icons as $iconFile)
                        @php $iconUrl = "/images/apps/{$iconFile}"; @endphp
                        <div class="icon-option cursor-pointer p-1 border-2 rounded-lg transition hover:border-indigo-400 @if(old('icon', $app->icon ?? '') == $iconUrl) border-indigo-600 bg-indigo-50 @else border-transparent @endif"
                            onclick="selectIcon('{{ $iconUrl }}', this)">
                            <img src="{{ $iconUrl }}" alt="{{ $iconFile }}" class="w-full h-auto rounded-md shadow-sm">
                            <p class="text-[10px] text-center mt-1 truncate">{{ $iconFile }}</p>
                        </div>
                    @empty
                        <p class="col-span-full text-center text-gray-500 py-4">
                            Nenhum ícone encontrado em <code>public/images/apps</code>.<br>
                            <a href="{{ route('admin.icon-generator') }}" class="text-indigo-600 hover:underline">Ir para o
                                Gerador de Ícones</a>
                        </p>
                    @endforelse
                </div>
                <p class="text-xs text-gray-500 mt-2">Os ícones são carregados da pasta <code>public/images/apps</code>.
                    Use o Gerador para adicionar novos.</p>
            </div>
        </div>
    </div>
</div>

<div class="flex items-center justify-end mt-6">
    <a href="{{ route('admin.apps.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
        Salvar
    </button>
</div>

<script>
    function selectIcon(path, element) {
        // Atualiza input
        document.getElementById('iconPathInput').value = path;

        // Atualiza UI (remover bordas de todos, adicionar no clicado)
        document.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.remove('border-indigo-600', 'bg-indigo-50');
            opt.classList.add('border-transparent');
        });
        element.classList.remove('border-transparent');
        element.classList.add('border-indigo-600', 'bg-indigo-50');
    }
</script>

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