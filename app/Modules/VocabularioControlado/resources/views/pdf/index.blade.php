<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocabulário Controlado — RIC-CPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; }
            h1 { font-size: 16pt; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body class="bg-white text-gray-800 p-8">

<div class="no-print mb-6 flex items-center justify-between">
    <a href="{{ route('vocabulario-controlado.index') }}"
       class="text-sm text-blue-600 hover:underline">← Voltar à pesquisa</a>
    <button onclick="window.print()"
            class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-5 py-2 rounded-lg transition">
        🖨 Imprimir / Salvar PDF
    </button>
</div>

<div class="text-center mb-8">
    <h1 class="text-2xl font-bold text-blue-900">Vocabulário Controlado</h1>
    <p class="text-sm text-gray-500 mt-1">RIC-CPS — {{ now()->format('d/m/Y') }}</p>
    <p class="text-sm text-gray-400">{{ $termos->count() }} termos aprovados / disponíveis</p>
</div>

<div class="columns-3 gap-4 text-sm">
    @foreach($termos as $termo)
    <div class="break-inside-avoid py-0.5 border-b border-gray-100">
        {{ $termo->palavra }}
    </div>
    @endforeach
</div>

<script>
    // Auto-abre a caixa de impressão ao carregar
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
