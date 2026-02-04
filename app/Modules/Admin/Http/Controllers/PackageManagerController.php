<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageManagerController extends Controller
{
    public function index()
    {
        $packages = Package::orderBy('name')->get();
        return view('Admin::packages.index', compact('packages'));
    }

    public function create()
    {
        return view('Admin::packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bg_color' => 'nullable|string|max:7', // Ex: #FFFFFF
        ]);

        Package::create($validated);

        return redirect()->route('admin.packages.index')->with('success', 'Pacote criado com sucesso!');
    }

    public function edit(Package $package)
    {
        return view('Admin::packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bg_color' => 'nullable|string|max:7',
        ]);

        $package->update($validated);

        return redirect()->route('admin.packages.index')->with('success', 'Pacote atualizado com sucesso!');
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('admin.packages.index')->with('success', 'Pacote excluído com sucesso!');
    }
}
