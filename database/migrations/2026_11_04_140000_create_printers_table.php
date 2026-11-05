<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('host', 253);
            $table->unsignedSmallInteger('port')->default(9004);
            $table->string('protocol_format', 10)->default('cr');
            $table->boolean('checksum_enabled')->default(false);
            $table->unsignedInteger('timeout_ms')->default(3000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
