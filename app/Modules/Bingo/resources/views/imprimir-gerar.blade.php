<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Bingo - {{ str_replace('.png', '', $tema) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300..700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Fredoka', sans-serif; background: #fff; color: #333; }
        .page { padding: 15px; max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; font-size: 28px; color: #d97706; margin-bottom: 15px; }
        .cartelas { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .cartela { width: 180px; border: 2px solid #d97706; border-radius: 10px; padding: 8px; background: #fff; break-inside: avoid; }
        .cartela h2 { text-align: center; font-size: 11px; color: #d97706; margin-bottom: 6px; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; }
        .cell { aspect-ratio: 1; border: 1.5px solid #e5e5e5; border-radius: 6px; position: relative; overflow: hidden; background-size: 500% 500%; background-repeat: no-repeat; }
        .cell span { position: absolute; bottom: 1px; right: 1px; background: rgba(217,119,6,0.85); color: #fff; font-size: 9px; font-weight: 900; padding: 0 3px; border-radius: 3px; line-height: 1.4; }

        .recortes { margin-top: 30px; page-break-before: always; }
        .recortes h2 { text-align: center; font-size: 22px; color: #d97706; margin-bottom: 15px; }
        .grid-recortes { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; max-width: 500px; margin: 0 auto; }
        .recorte { aspect-ratio: 1; border: 2px dashed #d97706; border-radius: 8px; position: relative; overflow: hidden; background-size: 500% 500%; background-repeat: no-repeat; display: flex; align-items: center; justify-content: center; }
        .recorte span { position: absolute; bottom: 2px; right: 2px; background: rgba(217,119,6,0.85); color: #fff; font-size: 11px; font-weight: 900; padding: 1px 4px; border-radius: 3px; line-height: 1.4; }

        @media print {
            .no-print { display: none !important; }
            .page { padding: 10px; }
            .cartela { border-color: #999; }
            .recortes { page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="page">
        <button onclick="window.print()" class="no-print" style="display:block;margin:0 auto 15px;padding:10px 30px;background:#d97706;color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:bold;cursor:pointer;font-family:Fredoka,sans-serif;">
            🖨️ Imprimir / Salvar PDF
        </button>

        <h1>🎉 BINGO! - {{ str_replace('.png', '', $tema) }}</h1>

        <div class="cartelas">
            @foreach ($cartelas as $idx => $numeros)
                <div class="cartela">
                    <h2>Cartela {{ $idx + 1 }}</h2>
                    <div class="grid">
                        @foreach ($numeros as $linha)
                            @foreach ($linha as $num)
                                @php
                                    $i = $num - 1;
                                    $col = $i % 5;
                                    $row = intdiv($i, 5);
                                @endphp
                                <div class="cell" style="background-image:url('{{ $temaUrl }}');background-position:{{ $col * 25 }}% {{ $row * 25 }}%;">
                                    <span>{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @if ($recortar)
            <div class="recortes">
                <h2>✂️ Recortar - {{ str_replace('.png', '', $tema) }}</h2>
                <div class="grid-recortes">
                    @for ($n = 1; $n <= 25; $n++)
                        @php
                            $i = $n - 1;
                            $col = $i % 5;
                            $row = intdiv($i, 5);
                        @endphp
                        <div class="recorte" style="background-image:url('{{ $temaUrl }}');background-position:{{ $col * 25 }}% {{ $row * 25 }}%;">
                            <span>{{ str_pad($n, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    @endfor
                </div>
            </div>
        @endif

        <p class="no-print" style="text-align:center;margin-top:20px;color:#999;font-size:13px;">
            <a href="{{ route('bingo.index') }}" style="color:#d97706;">← Voltar</a>
        </p>
    </div>
</body>
</html>
