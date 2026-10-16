<?php

namespace App\Modules\QuebraCabeca\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\QuebraCabeca\Models\Grupo;
use App\Modules\QuebraCabeca\Models\Imagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuebraCabecaController extends Controller
{
    private const EXTENSOES_PADRAO = ['jpg', 'jpeg', 'png', 'webp'];

    private function pastaPadrao(): string
    {
        return dirname(__DIR__, 2).'/imagens';
    }

    /**
     * Imagens padrão: todos os arquivos da pasta `imagens/` do módulo.
     * O nome exibido vem de `imagens/nomes.json` (arquivo sem extensão => nome); sem entrada, usa o nome do arquivo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function imagensPadrao(): array
    {
        $imagens = [];
        $arquivoNomes = $this->pastaPadrao().'/nomes.json';
        $nomes = is_file($arquivoNomes) ? (json_decode((string) file_get_contents($arquivoNomes), true) ?: []) : [];

        foreach (glob($this->pastaPadrao().'/*') ?: [] as $caminho) {
            if (! in_array(strtolower(pathinfo($caminho, PATHINFO_EXTENSION)), self::EXTENSOES_PADRAO, true)) {
                continue;
            }

            $arquivo = basename($caminho);
            $slug = pathinfo($arquivo, PATHINFO_FILENAME);

            $imagens[] = [
                'chave' => 'p-'.$slug,
                'nome' => $nomes[$slug] ?? ucfirst(str_replace(['-', '_'], ' ', $slug)),
                'url' => route('quebra-cabeca.padrao', $arquivo),
                'orientacao' => $this->orientacao($caminho),
                'grupo_id' => null,
                'padrao' => true,
            ];
        }

        return $imagens;
    }

    public function padrao(string $arquivo): BinaryFileResponse
    {
        $caminho = $this->pastaPadrao().'/'.basename($arquivo);

        abort_unless(is_file($caminho), 404);

        return response()->file($caminho, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function index(Request $request): View
    {
        $padrao = $this->imagensPadrao();

        $enviadas = Imagem::where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Imagem $imagem) => [
                'chave' => 'u'.$imagem->id,
                'id' => $imagem->id,
                'nome' => $imagem->nome,
                'url' => route('quebra-cabeca.imagens.show', $imagem),
                'orientacao' => $imagem->orientacao,
                'grupo_id' => $imagem->grupo_id,
                'padrao' => false,
                'mover' => route('quebra-cabeca.imagens.update', $imagem),
                'excluir' => route('quebra-cabeca.imagens.destroy', $imagem),
            ])
            ->all();

        $grupos = Grupo::where('user_id', $request->user()->id)
            ->orderBy('nome')
            ->get()
            ->map(fn (Grupo $grupo) => [
                'id' => $grupo->id,
                'nome' => $grupo->nome,
                'excluir' => route('quebra-cabeca.grupos.destroy', $grupo),
            ])
            ->all();

        return view('QuebraCabeca::index', compact('padrao', 'enviadas', 'grupos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'nullable|string|max:100',
            'imagem' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:8192',
            'grupo_id' => ['nullable', $this->regraGrupo($request)],
        ]);

        $arquivo = $request->file('imagem');

        Imagem::create([
            'user_id' => $request->user()->id,
            'grupo_id' => $validated['grupo_id'] ?? null,
            'orientacao' => $this->orientacao($arquivo->getRealPath()),
            'nome' => ($validated['nome'] ?? null) ?: pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME),
            'caminho' => $arquivo->store('quebra-cabeca/'.$request->user()->id, 'public'),
        ]);

        return redirect()->route('quebra-cabeca.index')->with('success', 'Imagem enviada!')->with('tela', 'biblioteca');
    }

    public function imagem(Request $request, Imagem $imagem): StreamedResponse
    {
        abort_unless($imagem->user_id === $request->user()->id, 403);

        return Storage::disk('public')->response($imagem->caminho);
    }

    public function destroy(Request $request, Imagem $imagem): RedirectResponse
    {
        abort_unless($imagem->user_id === $request->user()->id, 403);

        Storage::disk('public')->delete($imagem->caminho);
        $imagem->delete();

        return redirect()->route('quebra-cabeca.index')->with('success', 'Imagem removida.')->with('tela', 'biblioteca');
    }

    public function update(Request $request, Imagem $imagem): RedirectResponse
    {
        abort_unless($imagem->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'grupo_id' => ['nullable', $this->regraGrupo($request)],
        ]);

        $imagem->update(['grupo_id' => $validated['grupo_id'] ?? null]);

        return redirect()->route('quebra-cabeca.index')->with('success', 'Imagem movida.')->with('tela', 'biblioteca');
    }

    public function storeGrupo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:60',
        ]);

        Grupo::create(['user_id' => $request->user()->id, 'nome' => $validated['nome']]);

        return redirect()->route('quebra-cabeca.index')->with('success', 'Grupo criado!')->with('tela', 'biblioteca');
    }

    public function destroyGrupo(Request $request, Grupo $grupo): RedirectResponse
    {
        abort_unless($grupo->user_id === $request->user()->id, 403);

        $grupo->delete();

        return redirect()->route('quebra-cabeca.index')->with('success', 'Grupo removido. As imagens foram mantidas, sem grupo.')->with('tela', 'biblioteca');
    }

    private function regraGrupo(Request $request): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('quebra_cabeca_grupos', 'id')->where('user_id', $request->user()->id);
    }

    private function orientacao(string|false $caminho): string
    {
        $tamanho = $caminho ? @getimagesize($caminho) : false;

        return $tamanho && $tamanho[1] > $tamanho[0] ? 'retrato' : 'paisagem';
    }
}
