<x-Admin::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gerador e Upload de Ícones
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- Criador de Ícone (Emoji) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4">Criar Ícone com Emoji</h3>

                        <div class="flex flex-col items-center gap-6">
                            <canvas id="iconCanvas" width="512" height="512"
                                class="w-48 h-48 rounded-[20%] shadow-lg"></canvas>

                            <form action="{{ route('admin.icon-generator.store') }}" method="POST" id="saveForm"
                                class="w-full space-y-4">
                                @csrf
                                <input type="hidden" name="image_data" id="imageData">

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Emoji</label>
                                    <input type="text" id="emojiInput" value="🚀"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-center text-4xl py-2">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cor de Fundo</label>
                                    <input type="color" id="colorInput" value="#1a1b26"
                                        class="mt-1 block w-full h-10 rounded-md border-gray-300">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nome do Arquivo (sem
                                        .png)</label>
                                    <input type="text" name="filename" id="filenameInput" value="meu-icone" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>

                                <button type="button" onclick="submitGeneratedIcon()"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                    Gerar e Salvar no Servidor
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Upload Manual -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4">Upload de Imagem Manual</h3>

                        <form action="{{ route('admin.icon-generator.store') }}" method="POST"
                            enctype="multipart/form-data" class="space-y-6">
                            @csrf

                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-indigo-400 transition cursor-pointer"
                                onclick="document.getElementById('fileInput').click()">
                                <p class="text-gray-600">Clique para selecionar ou arraste uma imagem (PNG recomendado)
                                </p>
                                <input type="file" name="image_file" id="fileInput" class="hidden" accept="image/*"
                                    onchange="previewFile(this)">
                                <img id="filePreview" class="mt-4 mx-auto max-h-32 rounded-lg hidden">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nome do Arquivo (Ex:
                                    meu-app.png)</label>
                                <input type="text" name="filename" placeholder="meu-app.png" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>

                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                Fazer Upload
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('iconCanvas');
        const ctx = canvas.getContext('2d');
        const emojiInput = document.getElementById('emojiInput');
        const colorInput = document.getElementById('colorInput');

        function drawIcon() {
            const emoji = emojiInput.value || ' ';
            const color = colorInput.value;

            // Fundo
            ctx.fillStyle = color;
            ctx.clearRect(0, 0, 512, 512);
            ctx.beginPath();
            roundRect(ctx, 0, 0, 512, 512, 100);
            ctx.fill();

            // Emoji
            ctx.font = '280px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(emoji, 256, 276);
        }

        function roundRect(ctx, x, y, width, height, radius) {
            ctx.beginPath();
            ctx.moveTo(x + radius, y);
            ctx.lineTo(x + width - radius, y);
            ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
            ctx.lineTo(x + width, y + height - radius);
            ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
            ctx.lineTo(x + radius, y + height);
            ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
            ctx.lineTo(x, y + radius);
            ctx.quadraticCurveTo(x, y, x + radius, y);
            ctx.closePath();
        }

        function submitGeneratedIcon() {
            drawIcon(); // Garante o desenho final
            document.getElementById('imageData').value = canvas.toDataURL('image/png');
            document.getElementById('saveForm').submit();
        }

        function previewFile(input) {
            const preview = document.getElementById('filePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        emojiInput.addEventListener('input', drawIcon);
        colorInput.addEventListener('input', drawIcon);

        // Desenho inicial
        window.onload = drawIcon;
    </script>
</x-Admin::layout>