<?php

namespace App\Modules\VocabularioControlado\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\VocabularioControlado\Models\ListaValores;
use App\Modules\VocabularioControlado\Models\Perfil;
use App\Modules\VocabularioControlado\Models\Vocabulario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SolicitacaoController extends Controller
{
    /**
     * Ponto de entrada do portal via iframe.
     * Pode receber ?mail= e ?nome= para identificar o usuário.
     * Sem esses parâmetros, exibe apenas a lista pública.
     */
    public function index(Request $request): View
    {
        $mail = trim((string) $request->query('mail', ''));
        $nome = trim((string) $request->query('nome', ''));

        $perfil = null;

        if ($mail !== '') {
            $perfil = Perfil::firstOrCreate(
                ['mail' => $mail],
                ['nome' => $nome, 'perfil' => 'bibliotecario']
            );
        }

        return view('VocabularioControlado::solicitacao.index', compact('perfil'));
    }

    /**
     * Fragmento HTML da lista pública para scroll infinito.
     */
    public function listaFragmento(Request $request): View
    {
        $palavraLista = trim((string) $request->query('palavra_lista', ''));

        $query = Vocabulario::whereIn('status', ['Disponível', 'Aprovado'])
            ->orderByRaw('TRIM(palavra)');

        if ($palavraLista !== '') {
            $query->where('palavra', 'LIKE', '%'.$palavraLista.'%');
        }

        $listaTermos = $query->simplePaginate(50, ['*'], 'pagina_lista');

        return view('VocabularioControlado::solicitacao._lista-completa-fragmento', compact('listaTermos'));
    }

    /**
     * Recebe a solicitação de novo termo (perfil: bibliotecario).
     */
    public function store(Request $request): View
    {
        $data = $request->validate([
            'palavra' => 'required|string|max:100',
            'resumo' => 'required|string',
            'unidade' => 'required|string|max:3',
            'funcao' => 'required|string|max:50',
            'mail' => 'required|email|max:200',
            'nome' => 'nullable|string|max:200',
        ]);

        $perfil = Perfil::firstOrCreate(
            ['mail' => $data['mail']],
            ['nome' => $data['nome'] ?? '', 'perfil' => 'bibliotecario']
        );

        $aviso = null;

        $existente = Vocabulario::where('palavra', $data['palavra'])->first();

        if ($existente) {
            $aviso = match ($existente->status) {
                'Solicitado' => "O termo <strong>{$existente->palavra}</strong> já foi solicitado e está aguardando aprovação.",
                'Aprovado' => "O termo <strong>{$existente->palavra}</strong> já foi aprovado e aguarda implantação.",
                'Disponível' => "O termo <strong>{$existente->palavra}</strong> já está disponível para uso.",
                'Não Autorizado' => $this->avisoNaoAutorizado($existente),
                default => null,
            };
        }

        if ($aviso === null && $existente === null) {
            Vocabulario::create([
                'palavra' => $data['palavra'],
                'resumo' => $data['resumo'],
                'unidade' => $data['unidade'],
                'funcao' => $data['funcao'],
                'solicitadoPor' => $data['mail'],
                'status' => 'Solicitado',
            ]);

            $this->notificarAprovadores($data);
        }

        return view('VocabularioControlado::solicitacao.index', compact('perfil', 'aviso'));
    }

    /**
     * Aprova ou reprova um termo (perfil: aprovador).
     */
    public function aprovar(Request $request): View
    {
        $mail = $request->input('mail', '');
        $perfil = Perfil::find($mail);

        $acao = $request->input('acao');

        if ($acao === 'aprovar') {
            $request->validate([
                'vocabulario_id' => 'required|integer',
                'mail' => 'required|email',
            ]);

            Vocabulario::where('id', $request->input('vocabulario_id'))
                ->update([
                    'status' => 'Aprovado',
                    'autorizadoPor' => $mail,
                ]);
        }

        if ($acao === 'excluir') {
            $request->validate([
                'idVocabulario' => 'required|integer',
                'sugestaoPara' => 'nullable|string|max:500',
                'motivoReprova' => 'required|string|max:1000',
                'termo' => 'required|string|max:100',
                'mail' => 'required|email',
            ]);

            $sugestaoIds = $this->resolverSugestoes(
                $request->input('sugestaoPara', ''),
                $request->input('termo'),
                $mail
            );

            Vocabulario::where('id', $request->input('idVocabulario'))
                ->update([
                    'status' => 'Não Autorizado',
                    'autorizadoPor' => $mail,
                    'motivoReprova' => $request->input('motivoReprova'),
                    'sugestaoPara' => implode(',', $sugestaoIds),
                ]);
        }

        $pendentes = Vocabulario::where('status', 'Solicitado')
            ->orderByDesc('dt_solicitado')
            ->get();

        $unidadeIds = $pendentes->pluck('unidade')->unique()->filter()->values()->toArray();

        $unidades = ListaValores::where('value_pairs_name', 'common_publisher')
            ->whereIn('stored_value', $unidadeIds)
            ->get()
            ->keyBy('stored_value');

        $todosTermos = Vocabulario::whereIn('status', ['Disponível', 'Aprovado'])
            ->orderByRaw('TRIM(palavra)')
            ->get();

        return view('VocabularioControlado::solicitacao.aprovador', compact('perfil', 'pendentes', 'unidades', 'todosTermos'));
    }

    /**
     * Marca termos aprovados como Disponível e gera XML (perfil: implantacao).
     */
    public function implantacao(Request $request): View
    {
        $mail = $request->input('mail', '');
        $perfil = Perfil::find($mail);

        if ($request->input('acao') === 'marcar') {
            Vocabulario::where('status', 'Aprovado')
                ->update(['status' => 'Disponível']);
        }

        $termos = Vocabulario::whereIn('status', ['Disponível', 'Aprovado'])
            ->orderByRaw('TRIM(palavra)')
            ->get();

        return view('VocabularioControlado::solicitacao.implantacao', compact('perfil', 'termos'));
    }

    // -------------------------------------------------------------------------

    private function avisoNaoAutorizado(Vocabulario $termo): string
    {
        $aviso = "O termo <strong>{$termo->palavra}</strong> foi solicitado, porém não teve uso autorizado.";

        $sugestoes = $termo->sugestoes();
        if ($sugestoes->isNotEmpty()) {
            $lista = $sugestoes->pluck('palavra')->implode(', ');
            $aviso .= " Sugestão de uso em substituição: {$lista}.";
            $aviso .= "<br><br>Justificativa<br>{$termo->motivoReprova}";
        }

        return $aviso;
    }

    private function notificarAprovadores(array $data): void
    {
        $aprovadores = Perfil::where('perfil', 'aprovador')->pluck('mail')->toArray();

        if (empty($aprovadores)) {
            return;
        }

        $corpo = "E-Mail automático de adição de novo termo\n\n"
            ."Termo: {$data['palavra']}\n"
            ."Função: {$data['funcao']}\n\n"
            ."Resumo\n{$data['resumo']}\n\n"
            .'Equipe RIC - CPS';

        Mail::raw($corpo, function ($msg) use ($aprovadores) {
            $msg->to($aprovadores)
                ->from('ric@cps.sp.gov.br', 'RIC-CPS - Vocabulário Controlado')
                ->subject('Adição de novo termo');
        });
    }

    /**
     * Resolve termos de sugestão, criando-os no banco caso não existam.
     * Retorna array de IDs.
     *
     * @return int[]
     */
    private function resolverSugestoes(string $sugestaoStr, string $termoOriginal, string $mail): array
    {
        if (trim($sugestaoStr) === '') {
            return [];
        }

        $separador = str_contains($sugestaoStr, ';') ? ';' : ',';
        $ids = [];

        foreach (explode($separador, $sugestaoStr) as $cadaTermo) {
            $termo = trim($cadaTermo);
            if ($termo === '') {
                continue;
            }

            $registro = Vocabulario::firstOrCreate(
                ['palavra' => $termo],
                [
                    'resumo' => "Adicionada em substituição ao termo {$termoOriginal}",
                    'solicitadoPor' => $mail,
                    'autorizadoPor' => $mail,
                    'status' => 'Aprovado',
                    'unidade' => '',
                    'funcao' => '',
                ]
            );

            $ids[] = $registro->id;
        }

        return $ids;
    }
}
