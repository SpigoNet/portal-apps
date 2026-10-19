{{-- Formulário de agendamento (criar e editar). Recebe $agendamento (novo ou existente). --}}
@php
    $editando = $agendamento->exists;
    $hhmm = fn (?string $hora, string $padrao) => substr($hora ?: $padrao, 0, 5);
    $diasMarcados = $editando
        ? collect($agendamento->dias_semana ?? [])->map(fn ($d) => (int) $d)->values()->all()
        : [1, 2, 3, 4, 5];
    $conteudosMarcados = $agendamento->conteudosDinamicos->pluck('id')->all();
@endphp

<form action="{{ $editando ? route('pidgey.agendamentos.update', $agendamento) : route('pidgey.agendamentos.store') }}" method="POST" class="space-y-4" x-data="{
    frequencia: @js(old('frequencia', $agendamento->frequencia ?? 'una_vez')),
    interpretar: @js((bool) old('interpretar', $editando ? $agendamento->interpretar : true)),
    imagem: @js((bool) old('enviar_imagem', $agendamento->enviar_imagem ?? false)),
    persona: @js(old('persona_slug', $agendamento->persona_slug ?? $personas->first()?->slug)),
    comAparencia: @js($personas->filter(fn ($p) => filled($p->aparencia) || filled($p->imagem_referencia))->pluck('slug')->values()),
    canal: @js(old('canal', $agendamento->canal ?? 'telegram')),
    dias: @js(array_map('intval', old('dias_semana', $diasMarcados))),
}">
    @csrf
    @if ($editando)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Persona</label>
            <select name="persona_slug" required x-model="persona"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                @foreach ($personas as $p)
                    <option value="{{ $p->slug }}">{{ $p->slug }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Canal</label>
            <select name="canal" x-model="canal"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                <option value="telegram">Telegram</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="email">E-mail</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Mensagem</label>
        <textarea name="mensagem" rows="3" required
            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400"
            placeholder="Escreva a mensagem que a persona deve enviar...">{{ old('mensagem', $agendamento->mensagem) }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Frequência</label>
            <select name="frequencia" x-model="frequencia"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                <option value="una_vez">Uma vez</option>
                <option value="diario">Diário (1x/dia)</option>
                <option value="semanal">Semanal</option>
                <option value="intervalo">Intervalo (a cada N min)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Horário</label>
            <input type="time" name="hora" value="{{ old('hora', $hhmm($agendamento->hora, '09:00')) }}"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400"
                x-bind:required="['una_vez','diario','semanal'].includes(frequencia)" />
        </div>

        <div x-show="frequencia === 'semanal'">
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Dia da semana</label>
            <select name="dia_semana"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                @foreach (['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $i => $d)
                    <option value="{{ $i }}" @selected((int) old('dia_semana', $agendamento->dia_semana ?? -1) === $i)>{{ $d }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Intervalo --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-show="frequencia === 'intervalo'">
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">A cada (minutos)</label>
            <input type="number" name="intervalo_minutos" value="{{ old('intervalo_minutos', $agendamento->intervalo_minutos ?? 120) }}" min="10" max="1440"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
            <small class="text-[11px] text-slate-500">Ex: 120 = de 2 em 2 horas</small>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Horário início</label>
            <input type="time" name="hora_inicio" value="{{ old('hora_inicio', $hhmm($agendamento->hora_inicio, '08:00')) }}"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Horário fim</label>
            <input type="time" name="hora_fim" value="{{ old('hora_fim', $hhmm($agendamento->hora_fim, '22:00')) }}"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
        </div>
    </div>

    {{-- Dias da semana (diario/intervalo) --}}
    <div x-show="['diario','intervalo'].includes(frequencia)" class="rounded-lg border border-white/10 bg-slate-900/40 p-3">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400">Dias</label>
            <div class="flex gap-2">
                <button type="button" @click="dias = [1,2,3,4,5]"
                    class="rounded bg-slate-700 px-2 py-1 text-[11px] font-bold text-slate-200 hover:bg-slate-600">Dias úteis</button>
                <button type="button" @click="dias = [0,1,2,3,4,5,6]"
                    class="rounded bg-slate-700 px-2 py-1 text-[11px] font-bold text-slate-200 hover:bg-slate-600">Todos</button>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach (['Dom' => 0,'Seg' => 1,'Ter' => 2,'Qua' => 3,'Qui' => 4,'Sex' => 5,'Sáb' => 6] as $nome => $valor)
                <label class="flex items-center gap-1 text-sm text-slate-300">
                    <input type="checkbox" name="dias_semana[]" value="{{ $valor }}" x-model.number="dias"
                        class="rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400" />
                    {{ $nome }}
                </label>
            @endforeach
        </div>
        <small class="text-[11px] text-slate-500">Deixe marcado "Dias úteis" para enviar só de Seg a Sex.</small>
    </div>

    {{-- Período (data início / fim) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="['diario','semanal','intervalo'].includes(frequencia)">
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Data início <span class="text-slate-500 normal-case">(opcional)</span></label>
            <input type="date" name="data_inicio" value="{{ old('data_inicio', $agendamento->data_inicio?->format('Y-m-d')) }}"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
            <small class="text-[11px] text-slate-500">Se vazio, começa imediatamente.</small>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Data fim <span class="text-slate-500 normal-case">(opcional)</span></label>
            <input type="date" name="data_fim" value="{{ old('data_fim', $agendamento->data_fim?->format('Y-m-d')) }}"
                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
            <small class="text-[11px] text-slate-500">Ex: remédio por 5 dias — após essa data o agendamento é encerrado.</small>
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-300">
        <input type="checkbox" name="interpretar" value="1" x-model="interpretar"
            class="rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400" />
        Reescrever no estilo da persona (IA)
    </label>

    <div>
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="enviar_imagem" value="1" x-model="imagem"
                class="rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400" />
            Enviar imagem do personagem (expressão conforme a mensagem)
        </label>
        <small class="block text-[11px] text-slate-500 ml-6">
            A aparência vem do perfil da persona (Alfred → Personas → "Aparência do personagem"); a IA gera a expressão e o sentimento de cada mensagem. Se a imagem falhar, vai só o texto.
        </small>
        <small class="block text-[11px] text-amber-300 ml-6" x-show="imagem && ! comAparencia.includes(persona)" style="display: none">
            <i class="fa-solid fa-triangle-exclamation"></i> Esta persona ainda não tem aparência cadastrada no perfil — a mensagem irá sem imagem.
        </small>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Modelo de IA</label>
        <select name="ai_model_id"
            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
            <option value="">Padrão do sistema</option>
            @foreach ($modelos as $m)
                <option value="{{ $m->id }}" @selected((string) old('ai_model_id', $agendamento->ai_model_id) === (string) $m->id)>{{ $m->nome }} ({{ $m->provedor->nome ?? '—' }})</option>
            @endforeach
        </select>
        <small class="text-[11px] text-slate-500">Modelo usado para reescrever a mensagem. Se vazio, usa o padrão do portal.</small>
    </div>

    <div>
        <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400">Conteúdos dinâmicos <span class="text-slate-500 normal-case">(opcional)</span></label>
            <a href="{{ route('pidgey.conteudos.index') }}" class="text-[11px] font-bold text-amber-400 hover:underline">
                Gerenciar
            </a>
        </div>
        <select name="conteudos_dinamico[]" multiple
            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
        @foreach ($conteudos as $c)
            <option value="{{ $c->id }}" @selected(in_array($c->id, array_map('intval', old('conteudos_dinamico', $conteudosMarcados)), true))>{{ $c->nome }} — {{ $c->tipo_label }}{{ $c->sistema ? ' (Sistema)' : '' }}</option>
        @endforeach
        </select>
        <small class="text-[11px] text-slate-500">São adicionados ao contexto da IA ao gerar a mensagem.</small>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit"
            class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-black uppercase tracking-widest text-slate-950 hover:bg-amber-400 transition">
            @if ($editando)
                <i class="fa-solid fa-floppy-disk"></i> Salvar alterações
            @else
                <i class="fa-solid fa-paper-plane"></i> Agendar
            @endif
        </button>
        @if ($editando)
            <a href="{{ route('pidgey.agendamentos.index') }}" class="text-sm font-bold text-slate-400 hover:text-slate-200">Cancelar</a>
        @endif
    </div>
</form>
