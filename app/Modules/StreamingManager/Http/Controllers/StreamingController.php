<?php

namespace App\Modules\StreamingManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\StreamingManager\Models\Streaming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StreamingController extends Controller
{
    public function index()
    {
        $streamings = Streaming::where('user_id', Auth::id())
            ->orWhereHas('members', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->get();

        return view('StreamingManager::index', compact('streamings'));
    }

    public function create()
    {
        return view('StreamingManager::create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'monthly_cost' => 'required|numeric|min:0',
        ]);

        $streaming = Auth::user()->streamings()->create($validated);

        return redirect()->route('streaming-manager.show', $streaming);
    }

    public function show(Streaming $streaming)
    {
        // Check access
        if ($streaming->user_id !== Auth::id() && !$streaming->members()->where('user_id', Auth::id())->exists()) {
            abort(403);
        }

        $streaming->load(['members.user', 'payments.user']);

        $ranking = $streaming->payments()
            ->where('status', 'approved')
            ->selectRaw('user_id, sum(amount) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user')
            ->get();

        return view('StreamingManager::show', compact('streaming', 'ranking'));
    }

    public function edit(Streaming $streaming)
    {
        if ($streaming->user_id !== Auth::id()) {
            abort(403);
        }

        return view('StreamingManager::edit', compact('streaming'));
    }

    public function update(Request $request, Streaming $streaming)
    {
        if ($streaming->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'monthly_cost' => 'required|numeric|min:0',
        ]);

        $streaming->update($validated);

        return redirect()->route('streaming-manager.show', $streaming);
    }

    public function destroy(Streaming $streaming)
    {
        if ($streaming->user_id !== Auth::id()) {
            abort(403);
        }

        $streaming->delete();

        return redirect()->route('streaming-manager.index');
    }
}
