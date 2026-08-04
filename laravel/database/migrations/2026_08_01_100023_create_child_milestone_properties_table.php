<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What this milestone asks the parent for, copied from the catalogue row. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_milestone_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_milestone_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_milestone_properties');
    }
};
