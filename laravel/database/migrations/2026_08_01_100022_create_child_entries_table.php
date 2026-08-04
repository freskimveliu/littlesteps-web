<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A memory. Written only on submit, which is what makes "this milestone is done"
 * a single EXISTS.
 *
 * child_milestone_id is unique so a milestone holds one memory, and nullable so
 * the free memories — which have no milestone — can repeat: MySQL does not collide
 * on repeated NULLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_milestone_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->date('date');
            // Every memory carries how it felt — the one thing a parent always knows.
            $table->string('mood');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['child_id', 'date']);
            $table->index(['child_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_entries');
    }
};
