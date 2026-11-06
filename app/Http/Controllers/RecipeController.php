<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\Recipe;
use App\Models\TransmissionLog;
use App\Services\KeyenceCommunicationService;
use App\Services\RecipeFieldKeyGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class RecipeController extends Controller
{
    private const FONT_PREVIEW_SIZES = [
        0 => 5, 1 => 7, 3 => 10, 5 => 16, 6 => 24, 7 => 24, 9 => 32, 10 => 32,
        11 => 32, 12 => 16, 13 => 32, 16 => 24, 17 => 9, 18 => 12, 20 => 14, 22 => 32,
    ];

    public function index(): View
    {
        return view('recipes.index', ['recipes' => Recipe::with('printer')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('recipes.form', ['recipe' => new Recipe(), 'printers' => Printer::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $recipe = Recipe::create($this->validated($request));

        return to_route('recipes.edit', $recipe)->with('success', 'Ricetta creata. Configura ora i campi operatore.');
    }

    public function edit(Recipe $recipe): View
    {
        return view('recipes.form', [
            'recipe' => $recipe->load('fields'),
            'printers' => Printer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $validated = $this->validated($request);
        $fields = $request->validate([
            'fields' => ['nullable', 'array', 'max:64'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:100'],
            'fields.*.input_type' => ['required_with:fields', 'in:text,date,number'],
            'fields.*.default_value' => ['nullable', 'string', 'max:250'],
            'fields.*.validation_rules' => ['nullable', 'string', 'max:150'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.printer_font' => ['required_with:fields', 'integer', 'between:0,22'],
            'fields.*.layout_x' => ['required_with:fields', 'integer', 'between:0,4095'],
            'fields.*.layout_y' => ['required_with:fields', 'integer', 'between:0,31'],
        ])['fields'] ?? [];

        $keyGenerator = app(RecipeFieldKeyGenerator::class);
        $usedKeys = [];
        foreach ($fields as $index => $field) {
            $fields[$index]['key'] = $keyGenerator->generate($field['label'], $usedKeys);
            $usedKeys[] = $fields[$index]['key'];
        }

        if (count($fields) > 65 - $validated['message_number']) {
            return back()->withInput()->withErrors(['fields' => 'Il numero messaggio iniziale non lascia abbastanza messaggi MK-G disponibili per tutti i campi.']);
        }

        DB::transaction(function () use ($recipe, $validated, $fields): void {
            $recipe->update($validated);
            $existingIds = [];

            foreach ($fields as $position => $field) {
                $attributes = [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'input_type' => $field['input_type'],
                    'default_value' => $field['default_value'] ?? null,
                    'validation_rules' => $field['validation_rules'] ?? null,
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'position' => $position,
                    'printer_message_number' => $validated['message_number'] + $position,
                    'printer_font' => $field['printer_font'],
                    'layout_x' => $field['layout_x'],
                    'layout_y' => $field['layout_y'],
                    'layout_width' => 220,
                    'layout_height' => 48,
                    'font_size' => self::FONT_PREVIEW_SIZES[$field['printer_font']],
                ];

                if (!empty($field['id'])) {
                    $recipe->fields()->whereKey($field['id'])->update($attributes);
                    $existingIds[] = (int) $field['id'];
                } else {
                    $existingIds[] = $recipe->fields()->create($attributes)->id;
                }
            }

            $recipe->fields()->whereNotIn('id', $existingIds)->delete();
        });

        return to_route('recipes.edit', $recipe)->with('success', 'Ricetta e campi operatore aggiornati.');
    }

    public function syncLayout(Recipe $recipe, KeyenceCommunicationService $keyence): RedirectResponse
    {
        $recipe->load(['printer', 'fields']);

        if (! $recipe->printer->is_active) {
            return back()->withErrors(['sync' => 'Il marcatore associato alla ricetta non è attivo.']);
        }

        $commands = [];
        $responses = [];

        try {
            $commands = $keyence->buildLayoutCommands($recipe);

            foreach ($commands as $command) {
                $responses[] = $keyence->send($recipe->printer, $command);
            }

            $this->logSynchronization($recipe, $commands, $responses, true);

            return back()->with('success', 'Layout MK-G sincronizzato correttamente.');
        } catch (RuntimeException $exception) {
            report($exception);
            $this->logSynchronization($recipe, $commands, $responses, false, $exception->getMessage());

            return back()->withErrors(['sync' => 'Sincronizzazione layout non completata: '.$exception->getMessage()]);
        }
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return to_route('recipes.index')->with('success', 'Ricetta eliminata.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'printer_id' => ['required', 'exists:printers,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'program_number' => ['required', 'integer', 'between:1,500'],
            'message_number' => ['required', 'integer', 'between:1,64'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function logSynchronization(Recipe $recipe, array $commands, array $responses, bool $successful, ?string $error = null): void
    {
        TransmissionLog::create([
            'printer_id' => $recipe->printer_id,
            'recipe_id' => $recipe->id,
            'command' => implode("\n", $commands),
            'payload' => ['commands' => $commands, 'responses' => $responses],
            'response' => implode("\n", $responses) ?: null,
            'was_successful' => $successful,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }
}
