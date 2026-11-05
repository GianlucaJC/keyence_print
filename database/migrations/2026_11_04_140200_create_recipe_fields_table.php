<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('input_type', 20)->default('text');
            $table->string('default_value')->nullable();
            $table->string('validation_rules')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['recipe_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_fields');
    }
};
