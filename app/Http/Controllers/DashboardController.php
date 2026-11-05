<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\Recipe;
use App\Models\TransmissionLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'printers' => Printer::where('is_active', true)->orderBy('name')->get(),
            'recipes' => Recipe::with('printer')->where('is_active', true)->orderBy('name')->get(),
            'recentLogs' => TransmissionLog::with(['printer', 'recipe'])->latest('sent_at')->limit(10)->get(),
        ]);
    }
}
