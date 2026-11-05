<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\TransmissionLog;
use App\Services\KeyenceCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class OperatorController extends Controller
{
    public function show(Recipe $recipe): View
    {
        abort_unless($recipe->is_active && $recipe->printer->is_active, 404);

        return view('operator.show', ['recipe' => $recipe->load(['printer', 'fields'])]);
    }

    public function send(Request $request, Recipe $recipe, KeyenceCommunicationService $keyence): RedirectResponse
    {
        $recipe->load(['printer', 'fields']);
        abort_unless($recipe->is_active && $recipe->printer->is_active, 404);

        $rules = [];
        foreach ($recipe->fields as $field) {
            $rules["values.{$field->key}"] = array_filter([
                $field->is_required ? 'required' : 'nullable',
                'string',
                'max:250',
                $field->validation_rules,
            ]);
        }

        $values = $request->validate($rules)['values'] ?? [];
        $text = implode('', array_map(
            fn ($field) => (string) ($values[$field->key] ?? ''),
            $recipe->fields->all()
        ));

        try {
            $command = $keyence->buildMessageCommand($recipe->program_number, $recipe->message_number, $text);
            $response = $keyence->send($recipe->printer, $command);

            $this->log($recipe, $command, $values, $response, true);

            return back()->with('success', "Dati trasmessi correttamente. Risposta: {$response}");
        } catch (RuntimeException $exception) {
            report($exception);
            $this->log($recipe, isset($command) ? $command : 'H2', $values, null, false, $exception->getMessage());

            return back()->withInput()->withErrors(['transmission' => $exception->getMessage()]);
        }
    }

    private function log(Recipe $recipe, string $command, array $payload, ?string $response, bool $successful, ?string $error = null): void
    {
        TransmissionLog::create([
            'printer_id' => $recipe->printer_id,
            'recipe_id' => $recipe->id,
            'command' => $command,
            'payload' => $payload,
            'response' => $response,
            'was_successful' => $successful,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }
}
