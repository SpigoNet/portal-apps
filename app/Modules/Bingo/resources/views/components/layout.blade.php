<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bingo! 🎉</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: 'Fredoka', sans-serif; }
        .bounce-in { animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .stamp { animation: stampAnim 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .float { animation: float 3s ease-in-out infinite; }
        .wiggle { animation: wiggle 0.5s ease-in-out; }
        @keyframes bounceIn { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.15); } 100% { transform: scale(1); opacity: 1; } }
        @keyframes stampAnim { 0% { transform: scale(3) rotate(-15deg); opacity: 0; } 100% { transform: scale(0.9) rotate(12deg); opacity: 0.9; } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes wiggle { 0%, 100% { transform: rotate(0); } 25% { transform: rotate(-5deg); } 75% { transform: rotate(5deg); } }
        .confetti-piece {
            position: fixed; width: 10px; height: 10px; top: -10px; z-index: 100;
            animation: confettiFall linear forwards;
        }
        @keyframes confettiFall {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        .cell-btn { transition: all 0.15s ease; }
        .cell-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .cell-btn:active { transform: scale(0.95); }
    </style>
</head>
<body class="font-[Fredoka] antialiased bg-gradient-to-br from-amber-100 via-yellow-50 to-orange-100 min-h-screen">
    {{ $slot }}
    @stack('scripts')
</body>
</html>
