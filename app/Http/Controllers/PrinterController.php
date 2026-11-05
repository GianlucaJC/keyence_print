<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\TransmissionLog;
use App\Services\KeyenceCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PrinterController extends Controller
{
    public function index(): View
    {
        return view('printers.index', ['printers' => Printer::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('printers.form', ['printer' => new Printer()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Printer::create($this->validated($request));

        return to_route('printers.index')->with('success', 'Marcatore creato.');
    }

    public function edit(Printer $printer): View
    {
        return view('printers.form', compact('printer'));
    }

    public function update(Request $request, Printer $printer): RedirectResponse
    {
        $printer->update($this->validated($request));

        return to_route('printers.index')->with('success', 'Configurazione del marcatore aggiornata.');
    }

    public function destroy(Printer $printer): RedirectResponse
    {
        if ($printer->recipes()->exists()) {
            return back()->withErrors(['printer' => 'Non è possibile eliminare un marcatore associato a una ricetta.']);
        }

        $printer->delete();

        return to_route('printers.index')->with('success', 'Marcatore eliminato.');
    }

    public function test(Printer $printer, KeyenceCommunicationService $keyence): RedirectResponse
    {
        try {
            $response = $keyence->requestSystemStatus($printer);
            $this->log($printer, 'SB', $response, true);

            return back()->with('success', "Connessione riuscita. Stato sistema: {$response}");
        } catch (RuntimeException $exception) {
            report($exception);
            $this->log($printer, 'SB', null, false, $exception->getMessage());

            return back()->withErrors(['connection' => $exception->getMessage()]);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:253', 'regex:/^[A-Za-z0-9.-]+$/'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'protocol_format' => ['required', 'in:cr,stx_etx'],
            'checksum_enabled' => ['boolean'],
            'timeout_ms' => ['required', 'integer', 'between:500,30000'],
            'is_active' => ['boolean'],
        ]) + [
            'checksum_enabled' => $request->boolean('checksum_enabled'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function log(Printer $printer, string $command, ?string $response, bool $successful, ?string $error = null): void
    {
        TransmissionLog::create([
            'printer_id' => $printer->id,
            'command' => $command,
            'response' => $response,
            'was_successful' => $successful,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }
}
