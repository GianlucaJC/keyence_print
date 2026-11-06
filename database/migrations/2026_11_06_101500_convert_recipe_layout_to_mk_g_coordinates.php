<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('recipe_fields')->orderBy('id')->each(function (object $field): void {
            DB::table('recipe_fields')
                ->where('id', $field->id)
                ->update([
                    'layout_x' => (int) round($field->layout_x * 4095 / 799),
                    'layout_y' => (int) round($field->layout_y * 31 / 299),
                ]);
        });
    }

    public function down(): void
    {
        DB::table('recipe_fields')->orderBy('id')->each(function (object $field): void {
            DB::table('recipe_fields')
                ->where('id', $field->id)
                ->update([
                    'layout_x' => (int) round($field->layout_x * 799 / 4095),
                    'layout_y' => (int) round($field->layout_y * 299 / 31),
                ]);
        });
    }
};
