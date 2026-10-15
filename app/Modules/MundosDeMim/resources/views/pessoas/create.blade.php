<x-MundosDeMim::layout>
    <div class="mm-wrap mm-wrap-estreito mm-stack" style="gap:32px">
        <div style="display:flex;flex-direction:column;gap:10px">
            <h1 class="mm-h1">Nova companhia</h1>
            <p class="mm-lead">Alguém especial para aparecer junto com você nos mundos. As fotos ficam isoladas na sua conta.</p>
        </div>

        <form action="{{ route('mundos-de-mim.pessoas.store') }}" method="POST" enctype="multipart/form-data" data-aguarde="Salvando a companhia e enviando a foto…" class="mm-card" style="display:flex;flex-direction:column;gap:24px">
            @csrf

            <div class="mm-campo">
                <label for="name">Nome</label>
                <input type="text" name="name" id="name" required class="mm-input" placeholder="Ex.: Maria, João, Rex" value="{{ old('name') }}">
                @error('name')<span class="mm-dica" style="color:#FFB09B">{{ $message }}</span>@enderror
            </div>

            <div class="mm-campo">
                <label for="relationship">Quem é para você</label>
                <select name="relationship" id="relationship" required class="mm-input">
                    <option value="" disabled {{ old('relationship') ? '' : 'selected' }}>Escolha uma opção</option>
                    @foreach(['Namorado(a)' => 'Companheiro(a) ou cônjuge', 'Filho(a)' => 'Filho(a)', 'Pai/Mãe' => 'Pai ou mãe', 'Amigo(a)' => 'Amigo(a)', 'Pet' => 'Pet', 'Outro' => 'Outro'] as $valor => $rotulo)
                        <option value="{{ $valor }}" {{ old('relationship') === $valor ? 'selected' : '' }}>{{ $rotulo }}</option>
                    @endforeach
                </select>
                <span class="mm-dica">Ajuda a acertar o clima do postal: romântico, familiar, de amigos.</span>
            </div>

            <div class="mm-campo">
                <label for="photo">Foto do rosto</label>
                <input id="photo" name="photo" type="file" accept="image/*" required class="mm-input" style="padding:12px">
                <span class="mm-dica">PNG ou JPG, até 5 MB. De frente e com boa luz.</span>
                @error('photo')<span class="mm-dica" style="color:#FFB09B">{{ $message }}</span>@enderror
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:flex-end;align-items:center">
                <a href="{{ route('mundos-de-mim.pessoas.index') }}" class="mm-btn mm-btn-contorno">Cancelar</a>
                <button type="submit" class="mm-btn mm-btn-primario">Salvar companhia</button>
            </div>
        </form>
    </div>
</x-MundosDeMim::layout>
