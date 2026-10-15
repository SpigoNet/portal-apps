<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#14122B">
    <title>Criar conta · Mundos de Mim</title>
    @include('MundosDeMim::partials.tema')
</head>
<body class="mm" style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;box-sizing:border-box">
    <a href="{{ route('mundos-de-mim.landing') }}" style="position:absolute;top:16px;left:16px;padding:12px;color:var(--mm-texto-suave);font-size:15px">&larr; Voltar</a>

    <main class="mm-card" style="width:100%;max-width:440px;padding:40px;margin:32px 0;box-sizing:border-box;display:flex;flex-direction:column;gap:28px">
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center">
            <x-MundosDeMim::logo :tamanho="48" fonte="28px" />
            <p style="margin:0;color:var(--mm-texto-suave);font-size:16px">Crie seu atlas e receba o primeiro postal</p>
        </div>

        @if ($errors->any())
            <div role="alert" class="mm-alerta mm-alerta-erro">
                <ul style="margin:0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('mundos-de-mim.register.submit') }}" style="display:flex;flex-direction:column;gap:20px">
            @csrf
            <div class="mm-campo">
                <label for="name">Nome completo</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="mm-input" autofocus autocomplete="name">
            </div>
            <div class="mm-campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="mm-input" autocomplete="email">
            </div>
            <div class="mm-campo">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required class="mm-input" autocomplete="new-password">
            </div>
            <div class="mm-campo">
                <label for="password_confirmation">Confirmar senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required class="mm-input" autocomplete="new-password">
            </div>

            <button type="submit" class="mm-btn mm-btn-primario mm-btn-bloco" style="padding:16px;font-size:17px">Começar a viajar</button>
        </form>

        <div style="text-align:center;font-size:15px;color:var(--mm-texto-suave);border-top:1px solid var(--mm-linha);padding-top:24px">
            Já tem um atlas? <a href="{{ route('mundos-de-mim.login') }}" style="color:var(--mm-bruma);font-weight:600">Entrar</a>
        </div>
    </main>
</body>
</html>
