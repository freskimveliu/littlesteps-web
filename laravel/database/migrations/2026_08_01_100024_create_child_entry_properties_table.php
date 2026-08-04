<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What the parent actually filled in — the weight, the height, the first word. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_entry_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_entry_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name')->nullable();
            $table->string('value')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['key', 'child_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_entry_properties');
    }
};
