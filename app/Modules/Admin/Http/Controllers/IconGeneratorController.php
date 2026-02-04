<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IconGeneratorController extends Controller
{
    public function index()
    {
        return view('Admin::icon-generator.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'image_data' => 'nullable|string', // Base64 from canvas
            'image_file' => 'nullable|image|max:2048', // Manual upload
        ]);

        $folderPath = public_path('images/apps');
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        $filename = $request->filename;
        if (!str_ends_with($filename, '.png')) {
            $filename .= '.png';
        }

        if ($request->hasFile('image_file')) {
            $request->file('image_file')->move($folderPath, $filename);
        } elseif ($request->filled('image_data')) {
            $data = $request->image_data;
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $data = base64_decode($data);
                file_put_contents($folderPath . '/' . $filename, $data);
            }
        } else {
            return back()->with('error', 'Nenhuma imagem fornecida.');
        }

        return back()->with('success', "Ícone {$filename} salvo com sucesso!");
    }
}
