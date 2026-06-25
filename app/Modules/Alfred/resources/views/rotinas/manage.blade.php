@extends('Alfred::layouts.app')

@section('title', 'Gerenciar Rotinas - Alfred')

@section('content')
<div class="page-header">
    <h2>⚙️ Gerenciar Rotinas</h2>
    <div class="page-actions">
        <a href="{{ route('alfred.rotinas.index') }}" class="btn btn-secondary">⬅ Voltar</a>
        <a href="{{ route('alfred.rotinas.create') }}" class="btn btn-success">+ Nova Rotina</a>
    </div>
</div>

<div class="card">
    <h3 class="section-title">📋 Todas as Rotinas</h3>
    
    @if($rotinas->count() > 0)
        <div class="list-group">
            @foreach($rotinas as $rotina)
                <div class="list-item">
                    <div class="flex-between" style="margin-bottom: 8px;">
                        <div style="font-weight: 600;">{{ $rotina->titulo }}</div>
                        <span class="badge" style="background: {{ $rotina->categoria_badge['cor'] }}; color: white;">
                            {{ $rotina->categoria_badge['label'] }}
                        </span>
                    </div>
                    
                    @if($rotina->descricao)
                        <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 8px;">{{ $rotina->descricao }}</div>
                    @endif
                    
                    <div class="flex-between" style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 12px;">
                        <span>🕐 {{ $rotina->descricao_recorrencia }}</span>
                        @if($rotina->horario_sugerido)
                            <span>⏰ {{ substr($rotina->horario_sugerido, 0, 5) }}</span>
                        @endif
                        @if($rotina->ativa)
                            <span style="color: var(--accent-green);">🟢 Ativa</span>
                        @else
                            <span style="color: var(--accent-red);">🔴 Inativa</span>
                        @endif
                    </div>
                    
                    <div class="btn-group">
                        <a href="{{ route('alfred.rotinas.edit', $rotina) }}" class="btn btn-sm btn-warning">✏️ Editar</a>
                        <form action="{{ route('alfred.rotinas.destroy', $rotina) }}" method="POST" onsubmit="return confirm('Tem certeza?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑️ Excluir</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <p>Nenhuma rotina cadastrada.</p>
            <a href="{{ route('alfred.rotinas.create') }}" class="btn btn-success mt-2">Criar primeira rotina</a>
        </div>
    @endif
</div>


<div class="card mt-3">
    <h3 class="section-title">🔔 Notificações Push</h3>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px;">
        Para receber notificações das suas rotinas, ative as notificações neste dispositivo.
    </p>
    
    <div class="btn-group-vertical" style="margin-bottom: 16px;">
        <button type="button" class="btn btn-purple" id="btn-enable-push" onclick="enablePushNotifications()">
            🔔 Ativar Notificações neste aparelho
        </button>
        <span id="push-status" style="font-size: 0.8rem; text-align: center; margin-top: 4px; color: var(--text-muted);"></span>
    </div>

    <h4 style="font-size: 1rem; margin-bottom: 12px; margin-top: 24px;">Testar Notificações</h4>
    <div class="btn-group">
        <form action="{{ route('push.test-now') }}" method="POST" style="flex: 1;">
            @csrf
            <button type="submit" class="btn btn-secondary btn-block">⚡ Testar Agora</button>
        </form>
        <form action="{{ route('push.test-delayed') }}" method="POST" style="flex: 1;">
            @csrf
            <button type="submit" class="btn btn-secondary btn-block">⏳ Em 30 segundos</button>
        </form>
    </div>
</div>

<script>
    const vapidPublicKey = '{{ config("webpush.vapid.public_key") }}';

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function enablePushNotifications() {
        const statusEl = document.getElementById('push-status');
        
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            statusEl.textContent = '❌ Push Notifications não suportadas neste navegador.';
            statusEl.style.color = 'var(--accent-red)';
            return;
        }

        try {
            statusEl.textContent = '⏳ Solicitando permissão...';
            
            const permission = await Notification.requestPermission();
            
            if (permission !== 'granted') {
                statusEl.textContent = '❌ Permissão negada pelo usuário.';
                statusEl.style.color = 'var(--accent-red)';
                return;
            }

            statusEl.textContent = '⏳ Registrando Service Worker...';
            const registration = await navigator.serviceWorker.register('/sw.js');
            
            statusEl.textContent = '⏳ Inscrevendo no servidor Push...';
            
            let subscription = await registration.pushManager.getSubscription();
            
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                });
            }

            // Enviar para o servidor
            statusEl.textContent = '⏳ Salvando no servidor...';
            
            const response = await fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(subscription)
            });

            if (response.ok) {
                statusEl.textContent = '✅ Notificações ativadas com sucesso!';
                statusEl.style.color = 'var(--accent-green)';
                document.getElementById('btn-enable-push').textContent = '✅ Notificações Ativas';
                document.getElementById('btn-enable-push').classList.remove('btn-purple');
                document.getElementById('btn-enable-push').classList.add('btn-success');
            } else {
                throw new Error('Falha ao salvar no servidor');
            }

        } catch (error) {
            console.error('Erro ao ativar push:', error);
            statusEl.textContent = '❌ Erro: ' + error.message;
            statusEl.style.color = 'var(--accent-red)';
        }
    }

    // Checar status atual
    window.addEventListener('load', async () => {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                const subscription = await registration.pushManager.getSubscription();
                if (subscription && Notification.permission === 'granted') {
                    document.getElementById('btn-enable-push').textContent = '✅ Notificações Ativas';
                    document.getElementById('btn-enable-push').classList.remove('btn-purple');
                    document.getElementById('btn-enable-push').classList.add('btn-success');
                    document.getElementById('push-status').textContent = 'Seu dispositivo está pronto para receber notificações.';
                }
            }
        }
    });
</script>

@endsection
