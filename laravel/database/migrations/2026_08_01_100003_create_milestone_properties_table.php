<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What a milestone asks for beyond a story and a photo — a weight, a height, a first word. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_properties');
    }
};
