<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The nudge on the home screen, picked for the age the child is in today. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('months_from')->nullable();
            $table->unsignedSmallInteger('months_to')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['months_from', 'months_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
