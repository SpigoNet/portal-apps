<?php

namespace App\Modules\ComfyQueue\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ComfyQueue\Models\Job;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $jobs = Job::orderBy('created_at', 'desc')->paginate(15);

        return view('ComfyQueue::index', compact('jobs'));
    }

    public function create()
    {
        return view('ComfyQueue::create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'            => 'required|string',
            'params'          => 'required|string',
            'required_models' => 'nullable|string',
        ]);

        $params = json_decode($validated['params'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['params' => 'Workflow deve ser um JSON válido.'])->withInput();
        }

        $requiredModels = null;
        if (! empty($validated['required_models'])) {
            $requiredModels = json_decode($validated['required_models'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['required_models' => 'Lista de modelos deve ser um JSON válido.'])->withInput();
            }
        }

        Job::create([
            'type'            => $validated['type'],
            'params'          => $params,
            'required_models' => $requiredModels,
            'status'          => 'pending',
        ]);

        return redirect()->route('comfy-queue.index')->with('success', 'Job criado com sucesso.');
    }
}
