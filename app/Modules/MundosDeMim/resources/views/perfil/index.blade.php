<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Meu Perfil Biométrico') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form action="{{ route('mundos-de-mim.perfil.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-8 border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Foto de Referência (IA)</h3>
                            <div class="flex items-center space-x-6">
                                <div class="shrink-0">
                                    @if(isset($attributes->photo_path))
                                        <img class="h-24 w-24 object-cover rounded-full border-2 border-indigo-200"
                                             src="{{ Storage::url($attributes->photo_path) }}"
                                             alt="Foto atual">
                                    @else
                                        <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700">Carregar nova foto</label>
                                    <p class="text-xs text-gray-500 mb-2">Use uma foto de rosto clara e bem iluminada para melhor reconhecimento.</p>
                                    <input type="file" name="photo" accept="image/*"
                                           class="block w-full text-sm text-gray-500
                                           file:mr-4 file:py-2 file:px-4
                                           file:rounded-full file:border-0
                                           file:text-sm file:font-semibold
                                           file:bg-indigo-50 file:text-indigo-700
                                           hover:file:bg-indigo-100">
                                    @error('photo')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">Detalhes Biométricos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="height" class="block font-medium text-sm text-gray-700">Altura (cm)</label>
                                <input type="number" name="height" id="height"
                                       value="{{ old('height', $attributes->height ?? '') }}"
                                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                            </div>

                            <div>
                                <label for="weight" class="block font-medium text-sm text-gray-700">Peso (kg)</label>
                                <input type="number" name="weight" id="weight" step="0.1"
                                       value="{{ old('weight', $attributes->weight ?? '') }}"
                                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                            </div>

                            <div>
                                <label for="body_type" class="block font-medium text-sm text-gray-700">Tipo de Corpo (Ex: atlético, slim)</label>
                                <input type="text" name="body_type" id="body_type"
                                       value="{{ old('body_type', $attributes->body_type ?? '') }}"
                                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full" required>
                            </div>

                            <div>
                                <label for="eye_color" class="block font-medium text-sm text-gray-700">Cor dos Olhos</label>
                                <input type="text" name="eye_color" id="eye_color"
                                       value="{{ old('eye_color', $attributes->eye_color ?? '') }}"
                                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full" required>
                            </div>

                            <div>
                                <label for="hair_type" class="block font-medium text-sm text-gray-700">Tipo de Cabelo</label>
                                <input type="text" name="hair_type" id="hair_type"
                                       value="{{ old('hair_type', $attributes->hair_type ?? '') }}"
                                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full" required>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Salvar Perfil Completo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
