<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A trophy this child earned.
 *
 * Copied, not joined: retuning a threshold or renaming a trophy must not rewrite
 * one a child has already been shown. trophy_id stays only to stop the same
 * trophy being awarded twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_trophies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trophy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon');
            $table->string('metric');
            $table->unsignedInteger('threshold');
            $table->unsignedSmallInteger('xp')->default(0);
            $table->string('reward')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['child_id', 'trophy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_trophies');
    }
};
