<?php

use App\Models\Recipe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_fields', function (Blueprint $table) {
            $table->unsignedTinyInteger('printer_message_number')->nullable()->after('position');
            $table->unsignedTinyInteger('printer_font')->default(6)->after('printer_message_number');
        });

        Recipe::query()->with('fields')->each(function (Recipe $recipe): void {
            foreach ($recipe->fields as $position => $field) {
                DB::table('recipe_fields')
                    ->where('id', $field->id)
                    ->update(['printer_message_number' => min(64, $recipe->message_number + $position)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('recipe_fields', function (Blueprint $table) {
            $table->dropColumn(['printer_message_number', 'printer_font']);
        });
    }
};
