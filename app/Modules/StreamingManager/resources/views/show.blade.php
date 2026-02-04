<x-StreamingManager::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $streaming->name }}
        </h2>
    </x-slot>

    <div class="py-12 text-gray-800">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Info & Login -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-bold">Informações da Assinatura</h3>
                        <p class="mt-2 text-sm">Custo Mensal: <strong>R$
                                {{ number_format($streaming->monthly_cost, 2, ',', '.') }}</strong></p>
                        <p class="text-sm">Saldo Disponível: <strong>R$
                                {{ number_format($streaming->balance, 2, ',', '.') }}</strong></p>
                        <p class="text-sm">Válido até: <strong
                                class="{{ $streaming->daysRemaining > 7 ? 'text-green-600' : 'text-red-600' }}">{{ $streaming->funds_until }}</strong>
                        </p>
                    </div>
                    @if($streaming->username || $streaming->password)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-bold mb-2">Login Compartilhado</h3>
                            <div class="space-y-2">
                                <p class="text-sm">Usuário: <code
                                        class="bg-gray-200 px-1 rounded">{{ $streaming->username }}</code></p>
                                <p class="text-sm">Senha: <code
                                        class="bg-gray-200 px-1 rounded">{{ $streaming->password }}</code></p>
                                <button onclick="copyLogin()"
                                    class="mt-2 text-xs bg-gray-600 text-white px-2 py-1 rounded hover:bg-gray-700">Copiar
                                    Tudo</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Ranking -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">Ranking de Pagadores</h3>
                <div class="space-y-2">
                    @forelse($ranking as $rank)
                        <div class="flex justify-between items-center border-b pb-1">
                            <span>{{ $loop->iteration }}. {{ $rank->user->name }}</span>
                            <span class="font-bold">R$ {{ number_format($rank->total, 2, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Nenhum pagamento aprovado ainda.</p>
                    @endforelse
                </div>
            </div>

            <!-- Members & Payments -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Members -->
                <div class="bg-white shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-4">Pessoas que Compartilham</h3>
                    <ul class="space-y-2 mb-4">
                        <li class="flex justify-between items-center">
                            <span>{{ $streaming->owner->name }} (Dono)</span>
                        </li>
                        @foreach($streaming->members as $member)
                            <li class="flex justify-between items-center">
                                <span>{{ $member->user ? $member->user->name : $member->email }}</span>
                                @if($streaming->user_id === Auth::id())
                                    <form action="{{ route('streaming-manager.members.destroy', $member) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 text-xs hover:underline">Remover</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if($streaming->user_id === Auth::id())
                        <form action="{{ route('streaming-manager.members.store', $streaming) }}" method="POST"
                            class="mt-4">
                            @csrf
                            <input type="email" name="email" placeholder="Email do amigo"
                                class="border rounded px-2 py-1 w-full text-sm" required>
                            <button class="mt-2 bg-blue-500 text-white px-4 py-1 rounded text-sm w-full">Adicionar
                                Amigo</button>
                        </form>
                    @endif
                </div>

                <!-- Funds -->
                <div class="bg-white shadow-xl sm:rounded-lg p-6 text-gray-800">
                    <h3 class="text-lg font-bold mb-4">Adicionar Fundos</h3>
                    
                    @if($streaming->user_id === Auth::id())
                        <!-- Atalhos de Membros -->
                        <div class="mb-4">
                            <p class="text-xs text-gray-500 mb-2">Atalhos rápidos para quem está pagando:</p>
                            <div class="flex flex-wrap gap-2" id="member-shortcuts">
                                <button type="button" onclick="selectPayer({{ Auth::id() }}, '{{ Auth::user()->name }} (Eu)')"
                                    class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-blue-400 hover:bg-blue-200 transition shortcut-btn"
                                    data-id="{{ Auth::id() }}">
                                    Eu
                                </button>
                                @foreach($streaming->members as $member)
                                    @if($member->user)
                                        <button type="button" onclick="selectPayer({{ $member->user->id }}, '{{ $member->user->name }}')"
                                            class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-gray-400 hover:bg-gray-200 transition shortcut-btn"
                                            data-id="{{ $member->user->id }}">
                                            {{ $member->user->name }}
                                        </button>
                                    @endif
                                @endforeach
                                <button type="button" onclick="selectGuest()"
                                    class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-yellow-400 hover:bg-yellow-200 transition shortcut-btn"
                                    id="guest-btn">
                                    Convidado
                                </button>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('streaming-manager.payments.store', $streaming) }}" method="POST">
                        @csrf
                        <input type="hidden" name="target_user_id" id="target_user_id" value="">
                        
                        <div class="space-y-2">
                             @if($streaming->user_id === Auth::id())
                                <p class="text-sm font-bold text-blue-600" id="payer-display">Pagando para: <span id="payer-name">Quem selecionar</span></p>
                            @endif
                            
                            <div class="flex space-x-2">
                                <input type="number" step="0.01" name="amount" placeholder="Valor (Ex: 15,00)"
                                    class="border rounded px-2 py-1 flex-grow text-sm" required>
                            </div>
                            @if($streaming->user_id === Auth::id())
                                <input type="text" name="note" id="note_input" placeholder="Nome do Pagador (Opcional se for você)"
                                    class="border rounded px-2 py-1 w-full text-sm hidden">
                            @endif
                            <button class="bg-green-500 text-white px-4 py-1 rounded text-sm w-full">Adicionar</button>
                        </div>
                    </form>

                    <script>
                        function selectPayer(userId, name) {
                            // Set target user
                            document.getElementById('target_user_id').value = userId;
                            
                            // Visual updates
                            document.getElementById('payer-name').textContent = name;
                            document.getElementById('note_input').classList.add('hidden');
                            document.getElementById('note_input').value = '';
                            
                            // Highlight button
                            resetButtons();
                            const btn = document.querySelector(`.shortcut-btn[data-id="${userId}"]`);
                            if(btn) btn.classList.add('ring-2', 'ring-offset-1', 'ring-blue-500');
                        }

                        function selectGuest() {
                            document.getElementById('target_user_id').value = '';
                            document.getElementById('payer-name').textContent = 'Convidado (Digite o nome abaixo)';
                            document.getElementById('note_input').classList.remove('hidden');
                            document.getElementById('note_input').focus();
                            
                            resetButtons();
                            document.getElementById('guest-btn').classList.add('ring-2', 'ring-offset-1', 'ring-yellow-500');
                        }

                        function resetButtons() {
                            document.querySelectorAll('.shortcut-btn').forEach(btn => {
                                btn.classList.remove('ring-2', 'ring-offset-1', 'ring-blue-500', 'ring-yellow-500');
                            });
                        }
                    </script>

                    <h4 class="font-bold mt-6 mb-2">Histórico de Pagamentos</h4>
                    <div class="max-h-60 overflow-y-auto">
                        @foreach($streaming->payments->sortByDesc('created_at') as $payment)
                            <div class="text-sm border-b py-2 flex justify-between items-center">
                                <div>
                                    <p>{{ $payment->payer_name }}: R$ {{ number_format($payment->amount, 2, ',', '.') }}
                                    </p>
                                    <span
                                        class="text-xs text-gray-500">{{ $payment->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div>
                                    @if($payment->status === 'pending')
                                        @if($streaming->user_id === Auth::id())
                                            <form action="{{ route('streaming-manager.payments.approve', $payment) }}"
                                                method="POST">
                                                @csrf
                                                <button class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Aprovar</button>
                                            </form>
                                        @else
                                            <span class="text-orange-500 font-bold">Pendente</span>
                                        @endif
                                    @else
                                        <span class="text-green-600 font-bold">Aprovado</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function copyLogin() {
            const user = '{{ $streaming->username }}';
            const pass = '{{ $streaming->password }}';
            const text = `Usuário: ${user}\nSenha: ${pass}`;
            navigator.clipboard.writeText(text).then(() => {
                alert('Dados de login copiados!');
            });
        }
    </script>
</x-StreamingManager::layout>