<?php

namespace App\Modules\BolaoReuniao\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BolaoMeeting;
use App\Models\BolaoGuess;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class BolaoController extends Controller
{
    public function index()
    {
        $activeMeeting = BolaoMeeting::where('status', 'open')->latest()->first();
        return view('BolaoReuniao::index', compact('activeMeeting'));
    }

    public function start(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        // Close any existing open meetings
        BolaoMeeting::where('status', 'open')->update([
            'status' => 'closed',
            'finished_at' => now()
        ]);

        $meeting = BolaoMeeting::create([
            'name' => $request->name,
            'status' => 'open',
            'user_id' => auth()->id()
        ]);

        return redirect()->route('bolao.index');
    }

    public function participate($id)
    {
        $meeting = BolaoMeeting::findOrFail($id);
        if ($meeting->status !== 'open') {
            return redirect()->route('bolao.results', $id);
        }
        return view('BolaoReuniao::participate', compact('meeting'));
    }

    public function storeGuess(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:bolao_meetings,id',
            'name' => [
                'required',
                'string',
                'max:255',
                // garante que o mesmo nome não seja repetido dentro da mesma reunião
                Rule::unique('bolao_guesses')->where(function ($query) use ($request) {
                    return $query->where('meeting_id', $request->meeting_id);
                }),
            ],
            'guess' => 'required'
        ]);

        $guess = BolaoGuess::create($request->all());

        return redirect()->route('bolao.thank-you', $guess->id);
    }

    public function thankYou($id)
    {
        $guess = BolaoGuess::with('meeting')->findOrFail($id);
        $meeting = $guess->meeting;

        return view('BolaoReuniao::thank_you', compact('meeting'));
    }

    public function end($id)
    {
        $meeting = BolaoMeeting::findOrFail($id);

        if ($meeting->user_id && $meeting->user_id !== auth()->id()) {
            abort(403, 'Apenas o criador da reunião pode encerrá-la.');
        }

        $this->closeMeeting($meeting);

        return redirect()->route('bolao.results', $id);
    }

    public function admin()
    {
        $activeMeeting = BolaoMeeting::where('status', 'open')->latest()->first();
        return view('BolaoReuniao::admin', compact('activeMeeting'));
    }

    public function forceEnd($id)
    {
        $meeting = BolaoMeeting::findOrFail($id);
        $this->closeMeeting($meeting);

        return redirect()->route('bolao.results', $id);
    }

    private function closeMeeting(BolaoMeeting $meeting): void
    {
        $now = now();
        $meeting->update([
            'status' => 'closed',
            'finished_at' => $now
        ]);

        $actualSeconds = $now->hour * 3600 + $now->minute * 60 + $now->second;

        foreach ($meeting->guesses as $guess) {
            $parts = explode(':', $guess->guess);
            $guessSeconds = ($parts[0] * 3600) + ($parts[1] * 60);
            $diff = abs($actualSeconds - $guessSeconds);
            $guess->update(['diff_seconds' => $diff]);
        }
    }

    public function results($id)
    {
        $meeting = BolaoMeeting::with([
            'guesses' => function ($q) {
                $q->orderBy('diff_seconds', 'asc');
            }
        ])->findOrFail($id);

        return view('BolaoReuniao::results', compact('meeting'));
    }

    public function checkStatus($id)
    {
        $meeting = BolaoMeeting::findOrFail($id);
        return response()->json(['status' => $meeting->status]);
    }
}
