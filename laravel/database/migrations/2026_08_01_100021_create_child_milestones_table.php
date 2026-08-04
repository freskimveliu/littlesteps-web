<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One thing to capture, on this child's map.
 *
 * is_editable records only where the row came from — the catalogue or the parent —
 * and grants nothing: a guided milestone can be renamed, moved and deleted like
 * any other, because the map belongs to the parent. is_dated is the one flag that
 * does decide something: a milestone that names a date cannot be rehomed.
 *
 * There is no hidden flag. A milestone that will never happen is deleted, and its
 * memory outlives it — see child_entries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('months_from')->nullable();
            $table->unsignedSmallInteger('typical_days')->nullable();
            $table->boolean('is_dated')->default(false);
            $table->unsignedSmallInteger('xp')->default(25);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_editable')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['child_chapter_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_milestones');
    }
};
