<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\Theme;
use App\Modules\MundosDeMim\Models\ThemeExample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminGalleryController extends Controller
{
    /**
     * Exibe o gerenciador de galeria.
     */
    public function index()
    {
        // 1. Listar imagens na área pública
        $publicPath = public_path('images/mundos-de-mim');
        $publicImages = [];
        if (File::exists($publicPath)) {
            $files = File::files($publicPath);
            foreach ($files as $file) {
                if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'])) {
                    $publicImages[] = [
                        'filename' => $file->getFilename(),
                        'url' => asset('images/mundos-de-mim/' . $file->getFilename()),
                        'path' => $file->getRealPath()
                    ];
                }
            }
        }

        // 2. Listar Temas e seus Exemplos
        $themes = Theme::with('examples')->orderBy('name')->get();

        return view('MundosDeMim::admin.gallery.index', compact('publicImages', 'themes'));
    }

    /**
     * Copia uma imagem de exemplo de um tema para a galeria pública.
     */
    public function copyToPublic(Request $request)
    {
        $request->validate([
            'example_id' => 'required|exists:mundos_de_mim_theme_examples,id'
        ]);

        $example = ThemeExample::findOrFail($request->example_id);
        $sourcePath = Storage::disk('public')->path($example->image_path);

        if (!File::exists($sourcePath)) {
            return back()->with('error', 'Arquivo de origem não encontrado.');
        }

        $publicDir = public_path('images/mundos-de-mim');
        if (!File::exists($publicDir)) {
            File::makeDirectory($publicDir, 0755, true);
        }

        $filename = basename($example->image_path);
        $destinationPath = $publicDir . '/' . $filename;

        // Se já existir, podemos renomear ou apenas sobrescrever. Vamos copiar.
        File::copy($sourcePath, $destinationPath);

        return back()->with('success', 'Imagem copiada para a galeria pública com sucesso!');
    }

    /**
     * Remove uma imagem da galeria pública.
     */
    public function deleteFromPublic(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        $filename = $request->filename;
        $path = public_path('images/mundos-de-mim/' . $filename);

        if (File::exists($path)) {
            File::delete($path);
            return back()->with('success', 'Imagem removida da galeria pública.');
        }

        return back()->with('error', 'Arquivo não encontrado.');
    }
}
