<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_fields', function (Blueprint $table) {
            $table->unsignedSmallInteger('layout_x')->default(20)->after('position');
            $table->unsignedSmallInteger('layout_y')->default(20)->after('layout_x');
            $table->unsignedSmallInteger('layout_width')->default(220)->after('layout_y');
            $table->unsignedSmallInteger('layout_height')->default(48)->after('layout_width');
            $table->unsignedTinyInteger('font_size')->default(24)->after('layout_height');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_fields', function (Blueprint $table) {
            $table->dropColumn(['layout_x', 'layout_y', 'layout_width', 'layout_height', 'font_size']);
        });
    }
};
