<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipeController extends Controller
{
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
            'fields.*.key' => ['required_with:fields', 'regex:/^[A-Za-z][A-Za-z0-9_]{0,49}$/'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:100'],
            'fields.*.input_type' => ['required_with:fields', 'in:text,date,number'],
            'fields.*.default_value' => ['nullable', 'string', 'max:250'],
            'fields.*.validation_rules' => ['nullable', 'string', 'max:150'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.layout_x' => ['required_with:fields', 'integer', 'between:0,799'],
            'fields.*.layout_y' => ['required_with:fields', 'integer', 'between:0,299'],
            'fields.*.layout_width' => ['required_with:fields', 'integer', 'between:20,800'],
            'fields.*.layout_height' => ['required_with:fields', 'integer', 'between:20,300'],
            'fields.*.font_size' => ['required_with:fields', 'integer', 'between:8,72'],
        ])['fields'] ?? [];

        $keys = array_column($fields, 'key');
        if (count($keys) !== count(array_unique($keys))) {
            return back()->withInput()->withErrors(['fields' => 'Ogni campo deve avere una chiave univoca.']);
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
                    'layout_x' => $field['layout_x'],
                    'layout_y' => $field['layout_y'],
                    'layout_width' => $field['layout_width'],
                    'layout_height' => $field['layout_height'],
                    'font_size' => $field['font_size'],
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
}
