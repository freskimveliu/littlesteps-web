<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferences, hung off whatever owns them. One row per key rather than a column
 * per preference, so a new toggle is a write rather than a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->morphs('settable');
            $table->string('key');
            $table->string('value');
            $table->timestamps();

            $table->unique(['settable_type', 'settable_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
