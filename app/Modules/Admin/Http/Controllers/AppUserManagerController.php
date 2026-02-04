<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PortalApp;
use App\Models\User;
use Illuminate\Http\Request;

class AppUserManagerController extends Controller
{
    public function index(PortalApp $app)
    {
        $app->load('users');
        $allUsers = User::orderBy('name')->get();
        return view('Admin::apps.users.index', compact('app', 'allUsers'));
    }

    public function store(Request $request, PortalApp $app)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,user',
        ]);

        if ($app->users()->where('user_id', $validated['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'Usuário já está vinculado a este app.']);
        }

        $app->users()->attach($validated['user_id'], ['role' => $validated['role']]);

        return back()->with('success', 'Usuário adicionado com sucesso!');
    }

    public function update(Request $request, PortalApp $app, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        $app->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Função do usuário atualizada!');
    }

    public function destroy(PortalApp $app, User $user)
    {
        $app->users()->detach($user->id);
        return back()->with('success', 'Usuário removido do app.');
    }
}
