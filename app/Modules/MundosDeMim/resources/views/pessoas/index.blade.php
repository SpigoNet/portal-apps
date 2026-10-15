<x-MundosDeMim::layout>
    <div class="mm-wrap mm-stack" style="gap:32px">
        <div style="display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:flex-end">
            <div style="display:flex;flex-direction:column;gap:10px;max-width:640px">
                <h1 class="mm-h1">Companhias</h1>
                <p class="mm-lead">Quem pode aparecer nos seus postais a dois ou em família. Quem está em casa fica de fora, sem apagar o cadastro.</p>
            </div>
            <a href="{{ route('mundos-de-mim.pessoas.create') }}" class="mm-btn mm-btn-primario">Adicionar companhia</a>
        </div>

        @if(session('success'))
            <div role="status" class="mm-alerta mm-alerta-ok">{{ session('success') }}</div>
        @endif

        @if($pessoas->isEmpty())
            <div class="mm-card" style="display:flex;flex-direction:column;gap:12px;align-items:flex-start">
                <h2 class="mm-display" style="margin:0;font-size:28px;font-weight:700">Você ainda viaja sozinho</h2>
                <p style="margin:0;color:var(--mm-texto-suave);font-size:16px;line-height:1.5">Adicione parceiro, filhos, amigos ou até o pet para ganhar postais a dois ou em família, como os de Natal.</p>
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
                @foreach($pessoas as $pessoa)
                    <div class="mm-card" style="display:flex;align-items:center;gap:16px;padding:20px">
                        @if($pessoa->photo_path)
                            <img src="{{ Storage::url($pessoa->photo_path) }}" alt="Foto de {{ $pessoa->name }}"
                                 style="width:64px;height:64px;border-radius:50%;object-fit:cover;flex:none;{{ $pessoa->is_active ? '' : 'filter:grayscale(1);opacity:.7' }}">
                        @else
                            <span aria-hidden="true" style="width:64px;height:64px;border-radius:50%;background:var(--mm-bruma);color:var(--mm-noite);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;flex:none">{{ mb_strtoupper(mb_substr($pessoa->name, 0, 1)) }}</span>
                        @endif
                        <div style="flex-grow:1;min-width:0;display:flex;flex-direction:column;gap:4px">
                            <b style="font-size:18px;overflow:hidden;text-overflow:ellipsis">{{ $pessoa->name }}</b>
                            <span style="font-size:14px;color:var(--mm-texto-suave)">{{ $pessoa->relationship }}</span>
                            <span class="mm-chip {{ $pessoa->is_active ? 'mm-chip-ok' : '' }}" style="align-self:flex-start;{{ $pessoa->is_active ? '' : 'color:var(--mm-texto-suave)' }}">{{ $pessoa->is_active ? 'Viajando' : 'Em casa' }}</span>
                        </div>
                        <form action="{{ route('mundos-de-mim.pessoas.toggle', $pessoa->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="mm-btn mm-btn-contorno" style="padding:12px 16px">{{ $pessoa->is_active ? 'Deixar em casa' : 'Chamar' }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-MundosDeMim::layout>
