<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a child can earn. metric and threshold are the rule; the copy on
 * child_trophies is what a child was actually shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trophies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon');
            $table->string('metric');
            $table->unsignedInteger('threshold');
            $table->unsignedSmallInteger('xp')->default(0);
            $table->string('reward')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['metric', 'threshold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trophies');
    }
};
