<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class LogViewerController extends Controller
{
    protected string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    public function index()
    {
        if (! File::exists($this->logPath)) {
            return view('Admin::admin.logs.index', [
                'logs' => collect(),
                'error' => 'Log file not found',
            ]);
        }

        $content = File::get($this->logPath);
        $lines = explode("\n", trim($content));

        $logs = collect(array_reverse($lines))
            ->filter(function ($line) {
                return ! empty(trim($line));
            })
            ->map(function ($line) {
                return $this->parseLogLine($line);
            })
            ->filter()
            ->take(500);

        return view('Admin::admin.logs.index', [
            'logs' => $logs,
            'error' => null,
        ]);
    }

    public function tail(int $lines = 100)
    {
        if (! File::exists($this->logPath)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $content = File::get($this->logPath);
        $allLines = explode("\n", trim($content));
        $tailLines = array_slice($allLines, -$lines);

        return response()->json([
            'lines' => array_reverse($tailLines),
        ]);
    }

    public function clear()
    {
        if (! File::exists($this->logPath)) {
            return redirect()->back()->with('error', 'Log file not found');
        }

        File::put($this->logPath, '');

        return redirect()->back()->with('success', 'Log cleared successfully');
    }

    public function download()
    {
        if (! File::exists($this->logPath)) {
            return redirect()->back()->with('error', 'Log file not found');
        }

        return Response::download($this->logPath, 'laravel.log', [
            'Content-Type' => 'text/plain',
        ]);
    }

    protected function parseLogLine(string $line): ?array
    {
        if (empty(trim($line))) {
            return null;
        }

        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.\w+:\s+(.*)$/';

        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $matches[3],
                'raw' => $line,
            ];
        }

        return [
            'timestamp' => '',
            'level' => 'INFO',
            'message' => $line,
            'raw' => $line,
        ];
    }
}
