<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocabulário Controlado — RIC-CPS</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.red-purple.min.css">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.js"></script>
    <link rel="stylesheet" href="https://spigo.net/comps/mdl/getmdl-select.min.css">
    <script defer src="https://spigo.net/comps/mdl/getmdl-select.min.js"></script>
</head>
<body>
<div class="mdl-layout mdl-js-layout">
    @unless($semMenu ?? false)
    <header class="mdl-layout__header">
        <div class="mdl-layout__header-row">
            <span class="mdl-layout-title">Vocabulário Controlado</span>
        </div>
    </header>
    @endunless
    <main class="mdl-layout__content" style="padding: 16px;">
        @yield('content')
    </main>
</div>
</body>
</html>
