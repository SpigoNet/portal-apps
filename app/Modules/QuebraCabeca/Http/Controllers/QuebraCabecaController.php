<?php

namespace App\Modules\QuebraCabeca\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\QuebraCabeca\Models\Imagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuebraCabecaController extends Controller
{
    /**
     * Imagens embutidas (SVG em data URI) disponíveis sem upload.
     *
     * @return array<int, array{nome: string, url: string}>
     */
    private function imagensPadrao(): array
    {
        $cenas = [
            'Pôr do sol' => '<defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#312e81"/><stop offset=".55" stop-color="#db2777"/><stop offset="1" stop-color="#fbbf24"/></linearGradient></defs><rect width="800" height="600" fill="url(#g)"/><circle cx="400" cy="380" r="110" fill="#fde68a"/><path d="M0 430 L160 300 L300 420 L470 260 L650 410 L800 330 V600 H0Z" fill="#1e1b4b"/><circle cx="130" cy="110" r="5" fill="#fff"/><circle cx="560" cy="80" r="4" fill="#fff"/><circle cx="700" cy="170" r="3" fill="#fff"/>',
            'Floresta' => '<rect width="800" height="600" fill="#bae6fd"/><circle cx="660" cy="100" r="60" fill="#fde047"/><rect y="420" width="800" height="180" fill="#15803d"/><path d="M80 430 L150 230 L220 430Z M220 430 L310 160 L400 430Z M420 430 L500 250 L580 430Z M590 430 L670 190 L750 430Z" fill="#166534"/><rect x="140" y="430" width="20" height="50" fill="#78350f"/><rect x="300" y="430" width="22" height="50" fill="#78350f"/><rect x="490" y="430" width="20" height="50" fill="#78350f"/><rect x="660" y="430" width="22" height="50" fill="#78350f"/>',
            'Mar' => '<rect width="800" height="600" fill="#7dd3fc"/><rect y="300" width="800" height="300" fill="#0369a1"/><path d="M0 360 Q100 320 200 360 T400 360 T600 360 T800 360 V600 H0Z" fill="#0284c7"/><path d="M0 460 Q100 420 200 460 T400 460 T600 460 T800 460 V600 H0Z" fill="#075985"/><path d="M300 300 L400 120 L500 300Z" fill="#f8fafc"/><rect x="396" y="120" width="8" height="190" fill="#475569"/><path d="M260 300 H540 L500 350 H300Z" fill="#b91c1c"/>',
            'Cidade' => '<rect width="800" height="600" fill="#0f172a"/><circle cx="640" cy="110" r="45" fill="#e2e8f0"/><rect x="60" y="300" width="110" height="300" fill="#334155"/><rect x="190" y="200" width="130" height="400" fill="#475569"/><rect x="340" y="260" width="100" height="340" fill="#334155"/><rect x="460" y="150" width="140" height="450" fill="#475569"/><rect x="620" y="320" width="120" height="280" fill="#334155"/><g fill="#fde047"><rect x="215" y="230" width="22" height="22"/><rect x="265" y="290" width="22" height="22"/><rect x="490" y="190" width="22" height="22"/><rect x="550" y="260" width="22" height="22"/><rect x="85" y="340" width="22" height="22"/><rect x="365" y="310" width="22" height="22"/><rect x="650" y="370" width="22" height="22"/></g>',
        ];

        $imagens = [];
        foreach ($cenas as $nome => $conteudo) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600">'.$conteudo.'</svg>';
            $imagens[] = ['nome' => $nome, 'url' => 'data:image/svg+xml;base64,'.base64_encode($svg)];
        }

        return $imagens;
    }

    public function index(Request $request): View
    {
        $imagens = $this->imagensPadrao();

        $enviadas = Imagem::where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Imagem $imagem) => [
                'id' => $imagem->id,
                'nome' => $imagem->nome,
                'url' => route('quebra-cabeca.imagens.show', $imagem),
                'excluir' => route('quebra-cabeca.imagens.destroy', $imagem),
            ])
            ->all();

        return view('QuebraCabeca::index', compact('imagens', 'enviadas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'nullable|string|max:100',
            'imagem' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);

        $arquivo = $request->file('imagem');

        Imagem::create([
            'user_id' => $request->user()->id,
            'nome' => ($validated['nome'] ?? null) ?: pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME),
            'caminho' => $arquivo->store('quebra-cabeca/'.$request->user()->id, 'public'),
        ]);

        return redirect()->route('quebra-cabeca.index')->with('success', 'Imagem enviada!');
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

        return redirect()->route('quebra-cabeca.index')->with('success', 'Imagem removida.');
    }
}
