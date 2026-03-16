<div class="space-y-4">
    <div>
        <x-input-label for="nome" :value="__('Nome do Provedor')" />
        <x-text-input id="nome" name="nome" type="text" class="block mt-1 w-full" :value="old('nome', $provedor->nome ?? '')" required
            autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('nome')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="driver" :value="__('Driver')" />
            <x-text-input id="driver" name="driver" type="text" class="block mt-1 w-full"
                :value="old('driver', $provedor->driver ?? '')" required placeholder="pollination, airforce, gemini..." />
            <x-input-error class="mt-2" :messages="$errors->get('driver')" />
        </div>

        <div>
            <x-input-label for="base_url" :value="__('Base URL da API')" />
            <x-text-input id="base_url" name="base_url" type="text" class="block mt-1 w-full"
                :value="old('base_url', $provedor->base_url ?? '')" />
            <x-input-error class="mt-2" :messages="$errors->get('base_url')" />
        </div>
    </div>

    <div>
        <x-input-label for="url_json_modelos" :value="__('URL JSON para Sincronização')" />
        <x-text-input id="url_json_modelos" name="url_json_modelos" type="text" class="block mt-1 w-full"
            :value="old('url_json_modelos', $provedor->url_json_modelos ?? '')" />
        <p class="text-xs text-gray-500 mt-1">URL para sincronização automática de modelos (Pollinations, Airforce,
            etc).</p>
        <x-input-error class="mt-2" :messages="$errors->get('url_json_modelos')" />
    </div>

    <div>
        <x-input-label for="api_key" :value="__('Token / API Key')" />
        <x-text-input id="api_key" name="api_key" type="password" class="block mt-1 w-full" :value="old('api_key', isset($provedor) && $provedor->api_key ? '********' : '')"
            placeholder="Deixe em branco para manter a atual ou se não houver" />
        <p class="text-xs text-gray-500 mt-1">Token de acesso para este provedor (armazenado de forma criptografada).
        </p>
        <x-input-error class="mt-2" :messages="$errors->get('api_key')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label :value="__('Tipos de Entrada Padrão')" />
            <div class="mt-2 space-y-2 flex flex-col">
                @foreach (['text' => 'Texto', 'image' => 'Imagem'] as $val => $label)
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="default_input_types[]" value="{{ $val }}"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            {{ in_array($val, old('default_input_types', $provedor->default_input_types ?? [])) ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('default_input_types')" />
        </div>

        <div>
            <x-input-label :value="__('Tipos de Saída Padrão')" />
            <div class="mt-2 space-y-2 flex flex-col">
                @foreach (['text' => 'Texto', 'image' => 'Imagem', 'video' => 'Vídeo', 'audio' => 'Áudio'] as $val => $label)
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="default_output_types[]" value="{{ $val }}"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            {{ in_array($val, old('default_output_types', $provedor->default_output_types ?? [])) ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('default_output_types')" />
        </div>
    </div>

    <div>
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1"
                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                {{ old('is_active', $provedor->is_active ?? true) ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Provedor ativo</span>
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
    </div>
</div>
