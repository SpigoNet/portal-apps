<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PortalApp;
use App\Models\User;
use Illuminate\Http\Request;

class AppManagerController extends Controller
{
    public function index()
    {
        $apps = PortalApp::orderBy('title')->get();
        return view('Admin::apps.index', compact('apps'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('Admin::apps.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_link' => 'required|string|unique:portal_apps,start_link',
            'icon' => 'nullable|string',
            'visibility' => 'required|in:public,private,specific',
            'users' => 'required_if:visibility,specific|array'
        ]);

        $app = PortalApp::create($validated);

        if ($request->visibility === 'specific' && $request->has('users')) {
            $app->users()->sync($request->users);
        }

        return redirect()->route('admin.apps.index')->with('success', 'Aplicativo criado com sucesso!');
    }

    public function edit(PortalApp $app)
    {
        $users = User::orderBy('name')->get();
        return view('Admin::apps.edit', compact('app', 'users'));
    }

    public function update(Request $request, PortalApp $app)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_link' => 'required|string|unique:portal_apps,start_link,' . $app->id,
            'icon' => 'nullable|string',
            'visibility' => 'required|in:public,private,specific',
            'users' => 'required_if:visibility,specific|array'
        ]);

        $app->update($validated);

        if ($request->visibility === 'specific' && $request->has('users')) {
            $app->users()->sync($request->users);
        } else {
            $app->users()->sync([]);
        }

        return redirect()->route('admin.apps.index')->with('success', 'Aplicativo atualizado com sucesso!');
    }

    public function destroy(PortalApp $app)
    {
        $app->delete();
        return redirect()->route('admin.apps.index')->with('success', 'Aplicativo excluído com sucesso!');
    }
}
