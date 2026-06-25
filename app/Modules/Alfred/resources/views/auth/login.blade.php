<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Alfred</title>
    <style>
        :root {
            --bg-primary: #f0f2f5;
            --bg-secondary: #ffffff;
            --text-primary: #1a1a2e;
            --text-secondary: #4a4a68;
            --accent-blue: #4361ee;
            --accent-purple: #8b5cf6;
        }

        [data-theme="dark"] {
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent-blue: #6366f1;
            --accent-purple: #a78bfa;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-purple) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: var(--bg-secondary);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { font-size: 2.5rem; color: var(--accent-blue); }
        .logo p { color: var(--text-secondary); margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 16px;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        .form-group input:focus { outline: none; border-color: var(--accent-blue); }
        .btn {
            width: 100%;
            padding: 16px;
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .error {
            background: #fee;
            color: #c00;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .success {
            background: #efe;
            color: #060;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    <div class="login-container">
        <div class="logo">
            <h1>🤖 Alfred</h1>
            <p>Seu assistente pessoal</p>
        </div>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" onsubmit="document.getElementById('submitBtn').disabled=true; document.getElementById('submitBtn').innerText='Entrando...';">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" id="submitBtn" class="btn">Entrar</button>
        </form>
    </div>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            document.querySelector('.theme-toggle').textContent = newTheme === 'dark' ? '☀️' : '🌙';
        }
        
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        document.querySelector('.theme-toggle').textContent = savedTheme === 'dark' ? '☀️' : '🌙';
    </script>
</body>
</html>
