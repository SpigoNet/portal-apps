<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    {{-- manifest.json removed --}}
    <link rel="preload" href="{{ asset('alfred-icon.png') }}" as="image">
    
    <title>@yield('title', 'Alfred')</title>
    <style>
        :root {
            --bg-primary: #f0f2f5;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f8f9fa;
            --text-primary: #1a1a2e;
            --text-secondary: #4a4a68;
            --text-muted: #6c757d;
            --border-color: #e1e4e8;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --accent-blue: #4361ee;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-orange: #f59e0b;
            --accent-purple: #8b5cf6;
            --header-bg: #2c3e50;
            --nav-bg: #ffffff;
            --nav-border: #e1e4e8;
            --input-bg: #ffffff;
            --input-border: #d1d5db;
            --card-bg: #ffffff;
        }

        [data-theme="dark"] {
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-tertiary: #252542;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border-color: #334155;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
            --accent-blue: #6366f1;
            --accent-green: #34d399;
            --accent-red: #f87171;
            --accent-orange: #fbbf24;
            --accent-purple: #a78bfa;
            --header-bg: #1e1e3f;
            --nav-bg: #1a1a2e;
            --nav-border: #334155;
            --input-bg: #252542;
            --input-border: #475569;
            --card-bg: #1a1a2e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            font-size: 16px;
            touch-action: manipulation;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: calc(80px + env(safe-area-inset-bottom, 0px));
            padding-left: env(safe-area-inset-left, 0px);
            padding-right: env(safe-area-inset-right, 0px);
            transition: background 0.3s, color 0.3s;
        }
        
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 16px;
        }

        header {
            background: var(--header-bg);
            color: white;
            padding: 12px 16px;
            padding-top: calc(12px + env(safe-area-inset-top, 0px));
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }
        
        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-logo img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        header h1 {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .theme-toggle {
            background: rgba(255,255,255,0.15);
            border: none;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: background 0.2s;
        }
        
        .theme-toggle:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .btn-dia-ruim {
            background: var(--accent-red);
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-height: 36px;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
            transition: transform 0.1s, opacity 0.1s;
        }
        
        .btn-dia-ruim:active {
            transform: scale(0.95);
        }
        
        .card {
            background: var(--card-bg);
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: background 0.3s, border-color 0.3s;
        }
        
        .card h2, .card h3 {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .card h2 { font-size: 1.25rem; margin-bottom: 12px; }
        .card h3 { font-size: 1rem; margin-bottom: 10px; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            min-height: 48px;
            background: var(--accent-blue);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn:active {
            transform: scale(0.97);
            opacity: 0.9;
        }
        
        .btn-success { background: var(--accent-green); box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); }
        .btn-warning { background: var(--accent-orange); box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2); }
        .btn-danger { background: var(--accent-red); box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2); }
        .btn-secondary { background: var(--text-muted); box-shadow: none; }
        .btn-purple { background: var(--accent-purple); box-shadow: 0 2px 4px rgba(139, 92, 246, 0.2); }
        
        .btn-sm {
            padding: 10px 16px;
            min-height: 40px;
            font-size: 0.875rem;
            border-radius: 10px;
        }
        
        .btn-block {
            width: 100%;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .btn-group .btn {
            flex: 1;
            min-width: 120px;
            white-space: nowrap;
        }
        
        .btn-group-vertical {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-group-vertical .btn {
            width: 100%;
        }
        
        .alert {
            padding: 14px 16px;
            margin-bottom: 16px;
            border-radius: 12px;
            font-size: 0.9375rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-color: rgba(16, 185, 129, 0.3);
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border-color: rgba(245, 158, 11, 0.3);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .alert-info {
            background: rgba(67, 97, 238, 0.1);
            color: #4f46e5;
            border-color: rgba(67, 97, 238, 0.3);
        }
        
        [data-theme="dark"] .alert-success { color: #34d399; }
        [data-theme="dark"] .alert-warning { color: #fbbf24; }
        [data-theme="dark"] .alert-danger { color: #f87171; }
        [data-theme="dark"] .alert-info { color: #818cf8; }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--nav-bg);
            display: flex;
            justify-content: space-around;
            padding: 8px 0 calc(8px + env(safe-area-inset-bottom, 0px));
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 9999;
            border-top: 1px solid var(--nav-border);
            transition: background 0.3s, border-color 0.3s;
        }
        
        .bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.6875rem;
            padding: 8px 12px;
            min-height: 48px;
            min-width: 64px;
            transition: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        .bottom-nav a.active {
            color: var(--accent-blue);
            font-weight: 600;
        }
        
        .bottom-nav a span.icon {
            font-size: 1.5rem;
            margin-bottom: 2px;
        }
        
        .bottom-nav a:active {
            background: rgba(67, 97, 238, 0.1);
            border-radius: 12px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            min-height: 48px;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            font-size: 16px;
            background: var(--input-bg);
            color: var(--text-primary);
            transition: border-color 0.2s, background 0.3s;
            -webkit-appearance: none;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-blue);
        }
        
        select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 44px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border-radius: 10px;
            border: 2px solid var(--border-color);
            cursor: pointer;
            font-weight: normal;
            min-height: 48px;
            transition: background 0.3s, border-color 0.3s;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 22px;
            height: 22px;
            min-width: 22px;
        }
        
        .list-group {
            list-style: none;
            padding: 0;
        }
        
        .list-item {
            padding: 16px;
            margin-bottom: 12px;
            background: var(--bg-secondary);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            transition: background 0.3s, border-color 0.3s;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-alta { background: var(--accent-red); color: white; }
        .badge-media { background: var(--accent-orange); color: white; }
        .badge-baixa { background: var(--accent-blue); color: white; }
        
        .progress-bar {
            width: 100%;
            height: 40px;
            background: var(--bg-tertiary);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-blue), #6366f1);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9375rem;
            min-width: 50px;
        }
        
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pendente { background: rgba(245, 158, 11, 0.15); color: var(--accent-orange); }
        .status-concluido { background: rgba(16, 185, 129, 0.15); color: var(--accent-green); }
        .status-pulado { background: rgba(100, 116, 139, 0.15); color: var(--text-muted); }
        
        .text-center { text-align: center; }
        .mt-1 { margin-top: 8px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .flex-center {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }
        
        .flex-start {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 12px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .page-header h2 {
            margin: 0;
        }
        
        .page-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        body.modo-dia-ruim .card {
            border: 2px solid var(--accent-red);
        }
        
        .sidebar-logo {
            display: none;
        }

        @media (min-width: 768px) {
            body {
                padding-bottom: env(safe-area-inset-bottom, 0px);
                padding-left: 240px;
            }
            
            header {
                margin-left: 0;
                padding-left: 24px;
                padding-right: 24px;
            }
            
            header .header-left {
                display: none; /* Hide header logo on desktop, as we have it in sidebar */
            }
            
            header .container {
                max-width: 100%;
                justify-content: flex-end; /* Align actions to the right */
            }
            
            .bottom-nav {
                top: 0;
                left: 0;
                right: auto;
                bottom: 0;
                width: 240px;
                flex-direction: column;
                justify-content: flex-start;
                padding-top: 0;
                border-top: none;
                border-right: 1px solid var(--nav-border);
                box-shadow: 2px 0 10px rgba(0,0,0,0.05);
                z-index: 101; /* Above header if needed */
            }
            
            .sidebar-logo {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
                margin-bottom: 20px;
                background: var(--header-bg);
                color: white;
                height: 60px; /* roughly matches header height */
            }
            
            .sidebar-logo img {
                width: 30px;
                height: 30px;
                border-radius: 8px;
            }
            
            .sidebar-logo h1 {
                font-size: 1.25rem;
                font-weight: 600;
                margin: 0;
            }
            
            .bottom-nav a {
                flex-direction: row;
                justify-content: flex-start;
                padding: 14px 20px;
                font-size: 1rem;
                min-height: auto;
                border-radius: 12px;
                margin: 4px 16px;
            }
            
            .bottom-nav a span.icon {
                margin-right: 14px;
                margin-bottom: 0;
                font-size: 1.25rem;
                width: 28px;
                text-align: center;
            }
            
            .bottom-nav a:hover {
                background: var(--bg-tertiary);
            }

            .container {
                max-width: 800px;
                margin: 0 auto;
            }
            
            .btn-group .btn {
                width: auto;
                min-width: 140px;
            }
            
            .card {
                padding: 24px;
            }
        }
        
        @media (min-width: 1024px) {
            .container {
                max-width: 1000px;
            }
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .alfred-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .alfred-icon-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .alfred-bounce {
            animation: bounce 0.6s ease infinite;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-15px) scale(1.05); }
        }
        
        .drop {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--accent-blue);
            border-radius: 50%;
            opacity: 0;
            animation: drop 1s ease infinite;
        }
        
        .drop-1 { bottom: 0; left: 50%; transform: translateX(-50%); animation-delay: 0s; }
        .drop-2 { bottom: 0; left: 35%; animation-delay: 0.3s; }
        .drop-3 { bottom: 0; right: 35%; animation-delay: 0.6s; }
        
        @keyframes drop {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(40px) scale(0.5); }
        }
        
        .loading-text-container {
            margin-top: 24px;
            height: 28px;
            position: relative;
            text-align: center;
        }
        
        .loading-message {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            opacity: 0;
            font-size: 18px;
            font-weight: 600;
            transition: opacity 0.3s;
            color: var(--text-primary);
        }
        
        .loading-message.show { opacity: 1; }
        
        body::-webkit-scrollbar { width: 0px; background: transparent; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .divider {
            height: 2px;
            background: var(--border-color);
            margin: 24px 0;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 24px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border-color);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10001;
            justify-content: center;
            align-items: flex-end;
        }
        
        .modal-content {
            background: var(--card-bg);
            padding: 24px 20px;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 24px;
        }
        
        .modal-actions .btn {
            flex: 1;
        }
        
        @media (min-width: 768px) {
            .modal-overlay { align-items: center; }
            .modal-content { border-radius: 16px; animation: fadeIn 0.2s ease-out; }
            @keyframes fadeIn {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
        }
    </style>
</head>
<body class="@yield('body-class', '')">
    <header>
        <div class="container">
            <div class="header-left">
                <img src="{{ asset('alfred-icon.png') }}" alt="Alfred" style="width: 36px; height: 36px; border-radius: 10px;">
                <h1>Alfred</h1>
            </div>
            <div class="header-actions">
                <button class="theme-toggle" onclick="toggleTheme()" title="Alternar tema">
                    <span id="theme-icon">🌙</span>
                </button>
                @if(!isset($modo_dia_ruim) || !$modo_dia_ruim)
                    <a href="{{ route('alfred.dia-ruim.ativar') }}" class="btn-dia-ruim">🆘</a>
                @endif
                <form method="POST" action="{{ route('alfred.logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="theme-toggle" title="Sair do sistema" style="background: rgba(239, 68, 68, 0.15);">
                        <span>🚪</span>
                    </button>
                </form>
            </div>
        </div>
    </header>
    
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif
        
        @if(session('warning'))
            <div class="alert alert-warning">
                <span>⚠️</span> {{ session('warning') }}
            </div>
        @endif
        
        @if(session('info'))
            <div class="alert alert-info">
                <span>ℹ️</span> {{ session('info') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger">
                <span>❌</span> {{ session('error') }}
            </div>
        @endif
        
        @yield('content')
    </div>
    
    <nav class="bottom-nav">
        <div class="sidebar-logo">
            <img src="{{ asset('alfred-icon.png') }}" alt="Alfred">
            <h1>Alfred</h1>
        </div>
        <a href="{{ route('alfred.dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="icon">🏠</span>
            <span>Início</span>
        </a>
        <a href="{{ route('alfred.rotinas.index') }}" class="{{ request()->routeIs('rotinas.*') ? 'active' : '' }}">
            <span class="icon">🔄</span>
            <span>Rotinas</span>
        </a>
        <a href="{{ route('alfred.tarefas.index') }}" class="{{ request()->routeIs('tarefas.*') ? 'active' : '' }}">
            <span class="icon">📋</span>
            <span>Tarefas</span>
        </a>
        <a href="{{ route('alfred.medicamentos.index') }}" class="{{ request()->routeIs('medicamentos.*') ? 'active' : '' }}">
            <span class="icon">💊</span>
            <span>Remédios</span>
        </a>
        <a href="{{ route('alfred.hidratacao.index') }}" class="{{ request()->routeIs('hidratacao.*') ? 'active' : '' }}">
            <span class="icon">💧</span>
            <span>Água</span>
        </a>
    </nav>
    
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            document.getElementById('theme-icon').textContent = newTheme === 'dark' ? '☀️' : '🌙';
        }
        
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        document.getElementById('theme-icon').textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        
        var loadingTimeouts = [];
        
        document.addEventListener('click', function(e) {
            var navLink = e.target.closest('.bottom-nav a');
            if (navLink && !navLink.classList.contains('active')) {
                navLink.classList.add('loading');
                showLoadingOverlay();
            }
        });
        
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    showLoadingOverlay();
                    submitBtn.disabled = true;
                }
            });
        });
        
        if (document.referrer && document.referrer.includes(window.location.hostname)) {
            showLoadingOverlay();
        }
        
        function showLoadingOverlay() {
            loadingTimeouts.forEach(function(timeout) { clearTimeout(timeout); });
            loadingTimeouts = [];
            
            var existing = document.getElementById('loading-overlay');
            if (existing) { existing.remove(); }
            
            var overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255, 255, 255, 0.95);
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                z-index: 99999; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            `;
            
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                overlay.style.background = 'rgba(15, 15, 35, 0.95)';
            }
            
            var alfredMessages = [
                "Consultando o oráculo...", "Alfred está penteando a gravata...",
                "Polindo a cartola...", "Verificando se o Bat-sinal está funcionando...",
                "Tomando café com o Bruce...", "Arnold tá te esperando...",
                "Treinando com a Liga da Justiça...", "Baixando Updates do Bat-computer...",
                "Organizando o Cinto de Utilidades...", "Alfred está checando as câmeras...",
                "Preparando o Batmóvel...", "Contando os AlfredCoins...",
                "Verificando sensores de movimento...", "Alfred está no intervalo do café ☕",
                "Sincronizando com a Batcaverna...", "Carregando arsenal...",
                "Checando inventário de gadgets...", "Mantendo a Wayne Enterprise funcionando...",
                "Alfred está resolvendo a papelada...", "Atualizando protocolos de segurança...",
                "O Batman está ocupado, sou só eu aqui...", "Preparando a Batcaverna para visita...",
                "Tentando não derramar o café...", "Verificando relatórios de vigilantes...",
                "Alfred precisa de férias...", "Sendo mais útil que o Robin...",
                "Processando sua solicitação...", "Carregando com estilo...",
                "Um momento, estou operando em modo econômico..."
            ];
            
            var randomMessage = alfredMessages[Math.floor(Math.random() * alfredMessages.length)];
            var textColor = isDark ? '#f1f5f9' : '#2c3e50';
            
            overlay.innerHTML = `
                <div class="alfred-loading">
                    <div class="alfred-icon-wrapper">
                        <div class="alfred-bounce" style="background: url('{{ asset('alfred-icon.png') }}') center/cover no-repeat; width: 80px; height: 80px; border-radius: 20px;"></div>
                        <div class="drop drop-1"></div>
                        <div class="drop drop-2"></div>
                        <div class="drop drop-3"></div>
                    </div>
                    <div class="loading-text-container">
                        <span class="loading-message show" style="position: static; transform: none; color: ${textColor};">${randomMessage}</span>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }
        
        function hideLoadingOverlay() {
            loadingTimeouts.forEach(function(timeout) {
                clearTimeout(timeout);
                clearInterval(timeout);
            });
            loadingTimeouts = [];
            
            var overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.3s';
                setTimeout(function() { overlay.remove(); }, 300);
            }
            
            document.querySelectorAll('.bottom-nav a.loading').forEach(function(link) {
                link.classList.remove('loading');
            });
        }
        
        window.addEventListener('load', function() {
            setTimeout(hideLoadingOverlay, 800);
        });
    </script>
</body>
</html>
