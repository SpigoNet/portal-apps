<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#14122B">
    <title>Entrar · Mundos de Mim</title>
    @include('MundosDeMim::partials.tema')
</head>
<body class="mm" style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;box-sizing:border-box">
    <a href="{{ route('mundos-de-mim.landing') }}" style="position:absolute;top:16px;left:16px;padding:12px;color:var(--mm-texto-suave);font-size:15px">&larr; Voltar</a>

    <main class="mm-card" style="width:100%;max-width:440px;padding:40px;margin:32px 0;box-sizing:border-box;display:flex;flex-direction:column;gap:28px">
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center">
            <x-MundosDeMim::logo :tamanho="48" fonte="28px" />
            <p style="margin:0;color:var(--mm-texto-suave);font-size:16px">Entre para ver o seu atlas</p>
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

        <form method="POST" action="{{ route('mundos-de-mim.login.submit') }}" style="display:flex;flex-direction:column;gap:20px">
            @csrf
            <div class="mm-campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="mm-input" autofocus autocomplete="email">
            </div>
            <div class="mm-campo">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required class="mm-input" autocomplete="current-password">
            </div>
            <label class="mm-opcao" style="padding:0"><input type="checkbox" name="remember"> Lembrar de mim</label>

            <button type="submit" class="mm-btn mm-btn-primario mm-btn-bloco" style="padding:16px;font-size:17px">Entrar</button>
        </form>

        <div style="text-align:center;font-size:15px;color:var(--mm-texto-suave);border-top:1px solid var(--mm-linha);padding-top:24px;display:flex;flex-direction:column;gap:8px">
            <span>Ainda não tem um atlas? <a href="{{ route('mundos-de-mim.register') }}" style="color:var(--mm-bruma);font-weight:600">Criar o meu</a></span>
            <span style="font-size:13px">Ou use sua conta do portal: <a href="{{ route('login') }}" style="text-decoration:underline">entrar pelo portal</a></span>
        </div>
    </main>
</body>
</html>
