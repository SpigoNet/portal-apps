@extends('Alfred::layouts.app')

@section('title', 'Categorias de Rotinas - Alfred')

@section('content')
<div class="card">
    <div class="page-header">
        <h2>🏷️ Categorias de Rotinas</h2>
        <a href="{{ route('alfred.admin.index') }}" class="btn btn-secondary">← Voltar</a>
    </div>

    <div class="card" style="background: var(--bg-tertiary);">
        <h3 class="mb-2">➕ Nova Categoria</h3>
        <form action="{{ route('alfred.admin.categorias-rotina.store') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.875rem;">Nome</label>
                    <input type="text" name="nome" required class="form-control" placeholder="Ex: Trabalho">
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.875rem;">Cor</label>
                    <input type="color" name="cor" value="#34495e" required style="width: 100%; height: 48px; border: 2px solid var(--border-color); border-radius: 12px;">
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.875rem;">Ícone</label>
                    <input type="text" name="icone" class="form-control" placeholder="💼">
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 0.875rem;">Ordem</label>
                    <input type="number" name="ordem" value="10" class="form-control" min="0">
                </div>
            </div>
            <button type="submit" class="btn btn-success" style="width: 100%;">Criar</button>
        </form>
    </div>

    <h3 class="section-title">Categorias Existentes</h3>
    
    @if($categorias->count() > 0)
        <div style="display: grid; gap: 12px;">
            @foreach($categorias as $categoria)
                <div class="list-item" style="border-left: 4px solid {{ $categoria->cor }};">
                    <div class="flex-between">
                        <div class="flex-start" style="gap: 12px;">
                            <span style="font-size: 1.5rem;">{{ $categoria->icone }}</span>
                            <div>
                                <div style="font-weight: 600;">{{ $categoria->nome }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Ordem: {{ $categoria->ordem }}</div>
                            </div>
                        </div>
                        <div class="flex-start" style="gap: 8px;">
                            @if(!$categoria->ativa)
                                <span class="badge" style="background: var(--text-muted);">Inativa</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editarCategoria({{ $categoria->id }}, '{{ $categoria->nome }}', '{{ $categoria->cor }}', '{{ $categoria->icone }}', {{ $categoria->ordem }}, {{ $categoria->ativa ? 'true' : 'false' }})">
                                ✏️
                            </button>
                            <form action="{{ route('alfred.admin.categorias-rotina.destroy', $categoria) }}" method="POST" onsubmit="return confirm('Tem certeza?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <p>Nenhuma categoria encontrada.</p>
        </div>
    @endif
</div>

<div id="modalEditar" class="modal-overlay">
    <div class="modal-content">
        <h3 class="mb-2">✏️ Editar Categoria</h3>
        <form id="formEditar" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" id="editNome" required class="form-control">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Cor</label>
                    <input type="color" name="cor" id="editCor" required style="width: 100%; height: 48px; border-radius: 12px;">
                </div>
                <div class="form-group">
                    <label>Ícone</label>
                    <input type="text" name="icone" id="editIcone" class="form-control">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" name="ordem" id="editOrdem" class="form-control" min="0">
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 8px; padding-top: 8px;">
                    <input type="checkbox" name="ativa" id="editAtiva" style="width: 24px; height: 24px;">
                    <label for="editAtiva" style="margin: 0;">Categoria Ativa</label>
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-success">Salvar</button>
                <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarCategoria(id, nome, cor, icone, ordem, ativa) {
    document.getElementById('editNome').value = nome;
    document.getElementById('editCor').value = cor;
    document.getElementById('editIcone').value = icone || '';
    document.getElementById('editOrdem').value = ordem;
    document.getElementById('editAtiva').checked = ativa;
    document.getElementById('formEditar').action = '/admin/categorias-rotina/' + id;
    document.getElementById('modalEditar').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modalEditar').style.display = 'none';
}

document.getElementById('modalEditar').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>
@endsection
