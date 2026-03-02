<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configurações de IA') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-6">
                        Configure qual provedor de IA será usado para cada tipo de geração padrão.
                    </p>

                    <form action="{{ route('mundos-de-mim.config.update') }}" method="POST">
                        @csrf

                        <div class="space-y-6">
                            @foreach($settings as $setting)
                                <div class="border-b pb-4">
                                    <h3 class="font-bold text-lg text-gray-800 mb-2">{{ $setting->name }}</h3>
                                    <select name="settings[{{ $setting->setting_key }}]" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Selecione um provedor --</option>
                                        @foreach($providers as $provider)
                                            <option value="{{ $provider->id }}" 
                                                {{ $setting->ai_provider_id == $provider->id ? 'selected' : '' }}>
                                                {{ $provider->name }} ({{ $provider->input_type }} → {{ $provider->output_type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
