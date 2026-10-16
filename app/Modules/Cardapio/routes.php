<?php

use App\Modules\Cardapio\Http\Controllers\AgendaController;
use App\Modules\Cardapio\Http\Controllers\AssistenteController;
use App\Modules\Cardapio\Http\Controllers\CasaController;
use App\Modules\Cardapio\Http\Controllers\CompraController;
use App\Modules\Cardapio\Http\Controllers\EstoqueController;
use App\Modules\Cardapio\Http\Controllers\HistoricoController;
use App\Modules\Cardapio\Http\Controllers\IngredienteController;
use App\Modules\Cardapio\Http\Controllers\McpController;
use App\Modules\Cardapio\Http\Controllers\PainelController;
use App\Modules\Cardapio\Http\Controllers\PerfilController;
use App\Modules\Cardapio\Http\Controllers\PessoaController;
use App\Modules\Cardapio\Http\Controllers\PlanoController;
use App\Modules\Cardapio\Http\Controllers\ReceitaController;
use App\Modules\Cardapio\Http\Controllers\RecorrenteController;
use App\Modules\Cardapio\Http\Controllers\RotinaController;
use App\Modules\Cardapio\Http\Controllers\SobraController;
use App\Modules\Cardapio\Http\Middleware\ResolverCasa;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Illuminate\Support\Facades\Route;

Route::prefix('cardapio')
    ->name('cardapio.')
    ->middleware(['auth', RegistrarAcesso::class.':Cardapio'])
    ->group(function () {
        Route::get('/casa/criar', [CasaController::class, 'criar'])->name('casa.criar');
        Route::post('/casa', [CasaController::class, 'salvar'])->name('casa.salvar');

        Route::middleware(ResolverCasa::class)->group(function () {
            Route::get('/', [PainelController::class, 'index'])->name('index');

            Route::resource('pessoas', PessoaController::class)->except('show');
            Route::get('/pessoas/{pessoa}/perfil', [PerfilController::class, 'show'])->name('perfil.show');
            Route::post('/pessoas/{pessoa}/restricoes', [PerfilController::class, 'salvarRestricao'])->name('perfil.restricoes.salvar');
            Route::delete('/pessoas/{pessoa}/restricoes/{restricao}', [PerfilController::class, 'removerRestricao'])->name('perfil.restricoes.remover');
            Route::post('/pessoas/{pessoa}/preferencias', [PerfilController::class, 'salvarPreferencia'])->name('perfil.preferencias.salvar');
            Route::delete('/pessoas/{pessoa}/preferencias/{preferencia}', [PerfilController::class, 'removerPreferencia'])->name('perfil.preferencias.remover');

            Route::resource('estoque', EstoqueController::class)->except('show')->parameters(['estoque' => 'item'])->names('estoque');
            Route::post('/estoque/{item}/baixar', [EstoqueController::class, 'baixar'])->name('estoque.baixar');

            Route::get('/planejamento', [PlanoController::class, 'index'])->name('planejamento.index');
            Route::post('/planejamento', [PlanoController::class, 'store'])->name('planejamento.store');
            Route::get('/planejamento/{plano}', [PlanoController::class, 'show'])->name('planejamento.show');
            Route::post('/planejamento/{plano}', [PlanoController::class, 'salvar'])->name('planejamento.salvar');
            Route::delete('/planejamento/{plano}', [PlanoController::class, 'destroy'])->name('planejamento.destroy');
            Route::post('/planejamento/{plano}/compras', [PlanoController::class, 'gerarCompras'])->name('planejamento.compras');
            Route::post('/planejamento/{plano}/otimizar', [PlanoController::class, 'otimizar'])->name('planejamento.otimizar');
            Route::post('/planejamento/{plano}/sincronizar', [PlanoController::class, 'sincronizar'])->name('planejamento.sincronizar');
            Route::post('/planejamento/{plano}/gerar', [PlanoController::class, 'gerar'])->name('planejamento.gerar');
            Route::post('/planejamento/{plano}/dia/{data}/gerar', [PlanoController::class, 'gerarDia'])->name('planejamento.gerar.dia')->where('data', '\d{4}-\d{2}-\d{2}');
            Route::post('/planejamento/{plano}/refeicoes/{refeicao}/gerar', [PlanoController::class, 'gerarRefeicao'])->name('planejamento.gerar.refeicao');
            Route::get('/planejamento/{plano}/refeicoes/{refeicao}/alternativas', [PlanoController::class, 'alternativas'])->name('planejamento.alternativas');
            Route::post('/planejamento/{plano}/refeicoes/{refeicao}/escolher', [PlanoController::class, 'escolher'])->name('planejamento.escolher');
            Route::post('/planejamento/{plano}/refeicoes/{refeicao}/consumir', [PlanoController::class, 'consumir'])->name('planejamento.consumir');

            Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
            Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
            Route::get('/compras/{lista}', [CompraController::class, 'show'])->name('compras.show');
            Route::post('/compras/recorrentes', [CompraController::class, 'recorrentes'])->name('compras.recorrentes');
            Route::get('/recorrentes', [RecorrenteController::class, 'index'])->name('recorrentes.index');
            Route::post('/recorrentes', [RecorrenteController::class, 'store'])->name('recorrentes.store');
            Route::post('/recorrentes/{produto}/alternar', [RecorrenteController::class, 'alternar'])->name('recorrentes.alternar');
            Route::delete('/recorrentes/{produto}', [RecorrenteController::class, 'destroy'])->name('recorrentes.destroy');
            Route::post('/compras/{lista}/itens/{item}/mover', [CompraController::class, 'mover'])->name('compras.itens.mover');
            Route::post('/compras/{lista}/itens', [CompraController::class, 'adicionarItem'])->name('compras.itens.store');
            Route::post('/compras/{lista}/itens/{item}/comprado', [CompraController::class, 'alternarComprado'])->name('compras.itens.comprado');
            Route::post('/compras/{lista}/itens/{item}/quantidade', [CompraController::class, 'atualizarQuantidade'])->name('compras.itens.quantidade');
            Route::delete('/compras/{lista}/itens/{item}', [CompraController::class, 'removerItem'])->name('compras.itens.destroy');
            Route::post('/compras/{lista}/estoque', [CompraController::class, 'adicionarAoEstoque'])->name('compras.estoque');
            Route::post('/compras/{lista}/concluir', [CompraController::class, 'concluir'])->name('compras.concluir');

            Route::get('/historico', [HistoricoController::class, 'index'])->name('historico.index');
            Route::post('/historico/{historico}/feedback', [HistoricoController::class, 'feedback'])->name('historico.feedback');

            Route::get('/sobras', [SobraController::class, 'index'])->name('sobras.index');
            Route::post('/sobras', [SobraController::class, 'store'])->name('sobras.store');
            Route::delete('/sobras/{item}', [SobraController::class, 'destroy'])->name('sobras.destroy');

            Route::resource('receitas', ReceitaController::class);
            Route::post('/receitas/{receita}/favoritar', [ReceitaController::class, 'favoritar'])->name('receitas.favoritar');

            Route::resource('ingredientes', IngredienteController::class)->except('show');

            Route::get('/assistente', [AssistenteController::class, 'index'])->name('assistente.index');
            Route::post('/assistente/interpretar', [AssistenteController::class, 'interpretar'])->middleware('throttle:20,1')->name('assistente.interpretar');
            Route::post('/assistente/aplicar', [AssistenteController::class, 'aplicar'])->name('assistente.aplicar');
            Route::post('/assistente/descartar', [AssistenteController::class, 'descartar'])->name('assistente.descartar');

            Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
            Route::post('/agenda/eventos', [AgendaController::class, 'salvarEvento'])->name('agenda.eventos.store');
            Route::delete('/agenda/eventos/{evento}', [AgendaController::class, 'removerEvento'])->name('agenda.eventos.destroy');
            Route::post('/agenda/ausencias', [AgendaController::class, 'salvarAusencia'])->name('agenda.ausencias.store');
            Route::delete('/agenda/ausencias/{ausencia}', [AgendaController::class, 'removerAusencia'])->name('agenda.ausencias.destroy');

            Route::get('/conexao-mcp', [McpController::class, 'conexao'])->name('mcp.conexao');

            Route::get('/rotina', [RotinaController::class, 'index'])->name('rotina.index');
            Route::post('/rotina', [RotinaController::class, 'salvar'])->name('rotina.salvar');
            Route::post('/rotina/refeicoes', [RotinaController::class, 'salvarRefeicoes'])->name('rotina.refeicoes');
        });
    });
