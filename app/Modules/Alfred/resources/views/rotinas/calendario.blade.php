@extends('Alfred::layouts.app')

@section('title', 'Calendário de Rotinas - Alfred')

@section('content')
<style>
    .calendar-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 10px;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(40px, 1fr));
        gap: 4px;
        min-width: 300px;
    }
    
    .calendar-header {
        display: grid;
        grid-template-columns: repeat(7, minmax(40px, 1fr));
        gap: 4px;
        margin-bottom: 8px;
        text-align: center;
        font-weight: 600;
        min-width: 300px;
    }
    
    .calendar-cell {
        padding: 4px;
        border-radius: 4px;
        min-height: 60px;
        display: flex;
        flex-direction: column;
    }
    
    .calendar-day-number {
        font-size: 0.85rem;
        margin-bottom: 4px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    
    .calendar-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
    }

    .routine-title-sm {
        font-size: 0.65rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        display: none;
    }
    
    .routine-summary {
        font-size: 0.7rem;
        display: block;
    }
    
    .calendar-week-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    /* Tablet and Desktop */
    @media (min-width: 768px) {
        .calendar-grid, .calendar-header {
            min-width: unset;
        }
        .calendar-cell {
            padding: 8px;
            min-height: 80px;
        }
        .calendar-day-number {
            font-size: 1rem;
        }
        .routine-title-sm {
            display: block;
            font-size: 0.75rem;
        }
        .routine-summary {
            display: none;
        }
        .calendar-week-grid {
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
    }
    
    /* Desktop Large */
    @media (min-width: 1024px) {
        .calendar-cell {
            min-height: 100px;
        }
    }
</style>
<div style="width: 100%;">
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="font-size: 1.75rem; margin: 0;">📅 Calendário de Rotinas</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('alfred.rotinas.index') }}" class="btn btn-secondary">← Voltar</a>
        </div>
    </div>

    {{-- Navegação e Controles --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        {{-- Navegação de Data --}}
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => $visualizacao, 'data' => $data->copy()->subMonth()->format('Y-m-d')]) }}" class="btn btn-sm">
                ←
            </a>
            <h3 style="margin: 0;">{{ $data->format('F Y') }}</h3>
            <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => $visualizacao, 'data' => $data->copy()->addMonth()->format('Y-m-d')]) }}" class="btn btn-sm">
                →
            </a>
        </div>

        {{-- Toggle Visualização --}}
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => 'dia', 'data' => $data->format('Y-m-d')]) }}" 
               class="btn btn-sm {{ $visualizacao == 'dia' ? 'btn-success' : 'btn-secondary' }}">
                Dia
            </a>
            <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => 'semana', 'data' => $data->format('Y-m-d')]) }}" 
               class="btn btn-sm {{ $visualizacao == 'semana' ? 'btn-success' : 'btn-secondary' }}">
                Semana
            </a>
            <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => 'mes', 'data' => $data->format('Y-m-d')]) }}" 
               class="btn btn-sm {{ $visualizacao == 'mes' ? 'btn-success' : 'btn-secondary' }}">
                Mês
            </a>
        </div>

        {{-- Ir para Hoje --}}
        <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => $visualizacao]) }}" class="btn btn-sm" style="background: #3498db; color: white;">
            Hoje
        </a>
    </div>

    {{-- Legenda --}}
    <div style="display: flex; gap: 16px; margin-bottom: 20px; font-size: 0.875rem; flex-wrap: wrap;">
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 12px; height: 12px; background: #27ae60; border-radius: 3px;"></span>
            Feita ✓
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 12px; height: 12px; background: #e74c3c; border-radius: 3px;"></span>
            Pendente
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 12px; height: 12px; background: #f39c12; border-radius: 3px;"></span>
            Pulada ⏭
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 12px; height: 12px; background: #9b59b6; border-radius: 3px;"></span>
            Medicamentos 💊
        </span>
    </div>

    {{-- Calendário Mensal --}}
    @if($visualizacao == 'mes')
        @php
            $inicioMes = $data->copy()->startOfMonth();
            $fimMes = $data->copy()->endOfMonth();
            $inicioCalendario = $inicioMes->copy()->startOfWeek(0); // Domingo
            $fimCalendario = $fimMes->copy()->endOfWeek(6); // Sábado
            $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        @endphp

        {{-- Cabeçalho Dias da Semana --}}
        <div class="calendar-header">
            @foreach($diasSemana as $dia)
                <div style="padding: 8px; background: #f8f9fa; border-radius: 4px;">{{ $dia }}</div>
            @endforeach
        </div>

        {{-- Grid do Calendário --}}
        <div class="calendar-container"><div class="calendar-grid">
            @php
                $diaAtual = $inicioCalendario->copy();
            @endphp

            @while($diaAtual <= $fimCalendario)
                @php
                    $ehMesAtual = $diaAtual->month == $data->month;
                    $ehHoje = $diaAtual->isToday();
                    
                    // Buscar rotinas para este dia
                    $rotinasDia = $rotinas->filter(function($rotina) use ($diaAtual) {
                        return $rotina->deveExecutarHoje($diaAtual);
                    });

                    // Contadores
                    $totalRotinas = $rotinasDia->count();
                    $feitas = 0;
                    $puladas = 0;
                    
                    foreach($rotinasDia as $rotina) {
                        if ($rotina->foiExecutadaHoje($diaAtual)) {
                            $feitas++;
                        } elseif ($rotina->foiPuladaHoje($diaAtual)) {
                            $puladas++;
                        }
                    }
                    
                    $pendentes = $totalRotinas - $feitas - $puladas;
                    
                    // Verificar se tomou medicamentos neste dia
                    $dataStr = $diaAtual->format('Y-m-d');
                    $medicamentosTomados = isset($medicamentosPorDia[$dataStr]) ? $medicamentosPorDia[$dataStr] : 0;

                    // Cor do dia baseado no progresso
                    if ($totalRotinas == 0) {
                        $corDia = '#f8f9fa';
                    } elseif ($pendentes == 0 && $totalRotinas > 0) {
                        $corDia = '#d5f4e6'; // Tudo feito
                    } elseif ($feitas > 0) {
                        $corDia = '#fff3cd'; // Parcial
                    } else {
                        $corDia = '#f8d7da'; // Nada feito
                    }
                @endphp

                <div class="calendar-cell" style="background: {{ $corDia }}; border: 2px solid {{ $ehHoje ? '#3498db' : ($ehMesAtual ? 'var(--border-color)' : 'var(--bg-tertiary)') }}; opacity: {{ $ehMesAtual ? 1 : 0.5 }};">
                    {{-- Número do Dia --}}
                    <div class="calendar-day-number" style="font-weight: {{ $ehHoje ? '700' : '600' }}; color: {{ $ehHoje ? '#3498db' : ($ehMesAtual ? 'var(--text-primary)' : 'var(--text-muted)') }};">
                        <span>{{ $diaAtual->day }}</span>
                        @if($medicamentosTomados > 0)
                            <span style="font-size: 0.65rem; background: #9b59b6; color: white; padding: 2px 6px; border-radius: 10px;">💊 {{ $medicamentosTomados }}</span>
                        @endif
                        @if($ehHoje)
                            <span style="font-size: 0.7rem; color: #3498db;">(Hoje)</span>
                        @endif
                    </div>

                    {{-- Resumo das Rotinas --}}
                    @if($totalRotinas > 0)
                        <div class="routine-summary">
                            @if($feitas > 0)
                                <div style="color: #27ae60;">✓ {{ $feitas }} feita(s)</div>
                            @endif
                            @if($pendentes > 0)
                                <div style="color: #e74c3c;">• {{ $pendentes }} pendente(s)</div>
                            @endif
                            @if($puladas > 0)
                                <div style="color: #f39c12;">⏭ {{ $puladas }} pulada(s)</div>
                            @endif
                        </div>

                        {{-- Lista completa das rotinas (todas aparecem) --}}
                        <div style="margin-top: 4px;">
                            @foreach($rotinasDia as $rotina)
                                @php
                                    $executada = $rotina->foiExecutadaHoje($diaAtual);
                                    $pulada = $rotina->foiPuladaHoje($diaAtual);
                                    $corRotina = $executada ? '#27ae60' : ($pulada ? '#f39c12' : '#e74c3c');
                                @endphp
                                <div class="routine-title-sm" style="color: {{ $corRotina }}; {{ $executada ? 'text-decoration: line-through;' : '' }}">
                                    {{ $rotina->categoria_badge['label'] }}: {{ $rotina->titulo }}
                                </div>
                            @endforeach
                        </div>
                    @elseif($medicamentosTomados > 0)
                        <div style="font-size: 0.7rem; color: #9b59b6; text-align: center; margin-top: 8px;">
                            💊 {{ $medicamentosTomados }} medicamento(s)
                        </div>
                    @else
                        <div style="font-size: 0.7rem; color: #999; text-align: center; margin-top: 20px;">
                            Sem rotinas
                        </div>
                    @endif
                </div>

                @php
                    $diaAtual->addDay();
                @endphp
            @endwhile
        </div></div>
    @endif

    {{-- Visualização Semanal --}}
    @if($visualizacao == 'semana')
        @php
            $inicioSemana = $data->copy()->startOfWeek(0);
            $fimSemana = $data->copy()->endOfWeek(6);
            $diasSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        @endphp

        <div class="calendar-week-grid">
            @for($i = 0; $i < 7; $i++)
                @php
                    $diaSemana = $inicioSemana->copy()->addDays($i);
                    $ehHoje = $diaSemana->isToday();
                    
                    $rotinasDia = $rotinas->filter(function($rotina) use ($diaSemana) {
                        return $rotina->deveExecutarHoje($diaSemana);
                    });
                @endphp

                <div style="border: 2px solid {{ $ehHoje ? '#3498db' : '#dee2e6' }}; border-radius: 8px; padding: 12px; background: {{ $ehHoje ? '#e3f2fd' : 'white' }};">
                    {{-- Cabeçalho do Dia --}}
                    <div style="text-align: center; border-bottom: 2px solid {{ $ehHoje ? '#3498db' : '#dee2e6' }}; padding-bottom: 8px; margin-bottom: 12px;">
                        <div style="font-weight: 700; color: {{ $ehHoje ? '#3498db' : '#333' }};">
                            {{ $diasSemana[$i] }}
                        </div>
                        <div style="font-size: 1.25rem; font-weight: 600;">
                            {{ $diaSemana->day }}
                        </div>
                    </div>

                    {{-- Lista de Rotinas --}}
                    @if($rotinasDia->count() > 0)
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            @foreach($rotinasDia as $rotina)
                                @php
                                    $executada = $rotina->foiExecutadaHoje($diaSemana);
                                    $pulada = $rotina->foiPuladaHoje($diaSemana);
                                    $corBorda = $executada ? '#27ae60' : ($pulada ? '#f39c12' : '#e74c3c');
                                    $corFundo = $executada ? '#d5f4e6' : ($pulada ? '#fff3cd' : '#f8d7da');
                                @endphp
                                <div style="padding: 8px; border-left: 4px solid {{ $corBorda }}; background: {{ $corFundo }}; border-radius: 4px; font-size: 0.875rem; {{ $executada ? 'text-decoration: line-through; opacity: 0.7;' : '' }}">
                                    <div style="font-weight: 600; margin-bottom: 2px;">
                                        {{ $rotina->titulo }}
                                    </div>
                                    @if($rotina->horario_sugerido)
                                        <div style="font-size: 0.75rem; color: #666;">
                                            🕐 {{ $rotina->horario_sugerido }}
                                        </div>
                                    @endif
                                    @if($executada)
                                        <div style="font-size: 0.75rem; color: #27ae60;">✓ Feita</div>
                                    @elseif($pulada)
                                        <div style="font-size: 0.75rem; color: #f39c12;">⏭ Pulada</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; color: #999; font-size: 0.875rem; padding: 20px 0;">
                            Sem rotinas
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    @endif

    {{-- Visualização Diária --}}
    @if($visualizacao == 'dia')
        @php
            $rotinasDia = $rotinas->filter(function($rotina) use ($data) {
                return $rotina->deveExecutarHoje($data);
            })->sortBy('horario_sugerido');
            
            $ehHoje = $data->isToday();
        @endphp

        <div style="text-align: center; margin-bottom: 24px;">
            <h3 style="margin: 0; color: {{ $ehHoje ? '#3498db' : '#333' }};">
                {{ $data->format('l, d \d\e F \d\e Y') }}
                @if($ehHoje)
                    <span style="font-size: 0.875rem; background: #3498db; color: white; padding: 4px 8px; border-radius: 12px;">Hoje</span>
                @endif
            </h3>
        </div>

        @if($rotinasDia->count() > 0)
            <div style="display: grid; gap: 12px; max-width: 600px; margin: 0 auto;">
                @foreach($rotinasDia as $rotina)
                    @php
                        $executada = $rotina->foiExecutadaHoje($data);
                        $pulada = $rotina->foiPuladaHoje($data);
                        $motivoPulo = $rotina->getMotivoPuloHoje($data);
                        $corBorda = $executada ? '#27ae60' : ($pulada ? '#f39c12' : '#e74c3c');
                        $corFundo = $executada ? '#d5f4e6' : ($pulada ? '#fff3cd' : '#f8d7da');
                    @endphp
                    <div style="padding: 16px; border-left: 6px solid {{ $corBorda }}; background: {{ $corFundo }}; border-radius: 8px; {{ $executada ? 'opacity: 0.7;' : '' }}">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div style="font-size: 1.125rem; font-weight: 600; {{ $executada ? 'text-decoration: line-through;' : '' }}">
                                {{ $rotina->titulo }}
                                @if($executada)
                                    <span style="color: #27ae60;">✓</span>
                                @elseif($pulada)
                                    <span style="color: #f39c12;">⏭</span>
                                @endif
                            </div>
                            <span class="badge" style="background: {{ $rotina->categoria_badge['cor'] }}; color: white;">
                                {{ $rotina->categoria_badge['label'] }}
                            </span>
                        </div>
                        
                        @if($rotina->descricao)
                            <div style="color: #666; margin-bottom: 12px;">
                                {{ $rotina->descricao }}
                            </div>
                        @endif

                        <div style="display: flex; gap: 16px; font-size: 0.875rem; color: #666; margin-bottom: 8px;">
                            @if($rotina->horario_sugerido)
                                <span>🕐 {{ $rotina->horario_sugerido }}</span>
                            @endif
                            <span>🔄 {{ $rotina->descricao_recorrencia }}</span>
                        </div>

                        @if($pulada && $motivoPulo)
                            <div style="background: #ffeaa7; padding: 8px; border-radius: 4px; font-size: 0.875rem; margin-top: 8px; margin-bottom: 12px;">
                                💭 Motivo do pulo: {{ $motivoPulo }}
                            </div>
                        @endif

                        {{-- Botões de Ação --}}
                        <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                            @if($executada)
                                {{-- Rotina Executada: Mostrar status e botão Desfazer --}}
                                <span style="color: #27ae60; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                    ✓ Concluída
                                </span>
                                <form action="{{ route('alfred.rotinas.desfazer-execucao', $rotina) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="data" value="{{ $data->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-sm" style="background: #6c757d; color: white; font-size: 0.75rem; padding: 4px 8px;">
                                        ↩ Desfazer
                                    </button>
                                </form>
                            @elseif($pulada)
                                {{-- Rotina Pulada: Mostrar status e botões Desfazer/Concluir --}}
                                <span style="color: #f39c12; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                    ⏭ Pulada
                                </span>
                                <form action="{{ route('alfred.rotinas.desfazer-pulo', $rotina) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="data" value="{{ $data->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-sm" style="background: #6c757d; color: white; font-size: 0.75rem; padding: 4px 8px;">
                                        ↩ Desfazer Pulo
                                    </button>
                                </form>
                                <form action="{{ route('alfred.rotinas.marcar-executada', $rotina) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="data" value="{{ $data->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-sm" style="background: #27ae60; color: white; font-size: 0.75rem; padding: 4px 8px;">
                                        ✓ Concluir Mesmo Assim
                                    </button>
                                </form>
                            @else
                                {{-- Rotina Pendente: Botões Concluir e Pular --}}
                                <form action="{{ route('alfred.rotinas.marcar-executada', $rotina) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="data" value="{{ $data->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-sm" style="background: #27ae60; color: white;">
                                        ✓ Concluir
                                    </button>
                                </form>
                                
                                {{-- Botão Pular com Modal --}}
                                <button type="button" class="btn btn-sm" style="background: #f39c12; color: white;" onclick="document.getElementById('modal-pulo-{{ $rotina->id }}').style.display='flex'">
                                    ⏭ Pular
                                </button>
                                
                                {{-- Modal de Pulo --}}
                                <div id="modal-pulo-{{ $rotina->id }}" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                                    <div style="background: white; padding: 24px; border-radius: 8px; max-width: 400px; width: 90%; margin: 20px;">
                                        <h4 style="margin-top: 0;">Pular Rotina</h4>
                                        <p style="color: #666; margin-bottom: 16px;">
                                            <strong>{{ $rotina->titulo }}</strong><br>
                                            <small>{{ $data->format('d/m/Y') }}</small>
                                        </p>
                                        <form action="{{ route('alfred.rotinas.pular-hoje', $rotina) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="data" value="{{ $data->format('Y-m-d') }}">
                                            <div style="margin-bottom: 16px;">
                                                <label style="display: block; margin-bottom: 4px; font-size: 0.875rem; color: #666;">
                                                    Motivo (opcional):
                                                </label>
                                                <textarea name="motivo" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" placeholder="Ex: Não estava me sentindo bem..."></textarea>
                                            </div>
                                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-pulo-{{ $rotina->id }}').style.display='none'">
                                                    Cancelar
                                                </button>
                                                <button type="submit" class="btn" style="background: #f39c12; color: white;">
                                                    Confirmar Pulo
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 60px 20px; color: #666;">
                <div style="font-size: 4rem; margin-bottom: 16px;">📅</div>
                <h3>Sem rotinas para este dia</h3>
                <p>Aproveite o dia livre!</p>
            </div>
        @endif

        {{-- Navegação entre dias --}}
        <div style="display: flex; justify-content: center; gap: 16px; margin-top: 32px;">
            <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => 'dia', 'data' => $data->copy()->subDay()->format('Y-m-d')]) }}" class="btn">
                ← Dia Anterior
            </a>
            <a href="{{ route('alfred.rotinas.calendario', ['visualizacao' => 'dia', 'data' => $data->copy()->addDay()->format('Y-m-d')]) }}" class="btn">
                Próximo Dia →
            </a>
        </div>
    @endif
</div>
@endsection
