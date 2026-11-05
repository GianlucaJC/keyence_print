<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmission_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('printer_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            $table->text('command');
            $table->text('response')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('was_successful');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['printer_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transmission_logs');
    }
};
