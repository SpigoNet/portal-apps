<?php

namespace App\Modules\StreamingManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\StreamingManager\Models\Streaming;
use App\Modules\StreamingManager\Models\StreamingMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    public function store(Request $request, Streaming $streaming)
    {
        if ($streaming->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        $streaming->members()->create([
            'email' => $validated['email'],
            'user_id' => $user?->id,
        ]);

        return redirect()->back()->with('success', 'Membro adicionado com sucesso!');
    }

    public function destroy(StreamingMember $member)
    {
        if ($member->streaming->user_id !== Auth::id()) {
            abort(403);
        }

        $member->delete();

        return redirect()->back()->with('success', 'Membro removido com sucesso!');
    }
}
