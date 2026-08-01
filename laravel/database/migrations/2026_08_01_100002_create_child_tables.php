<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The child's own journey.
 *
 * Chapters and milestones are copied from the catalogue at provisioning and are
 * never joined back at read time, so an admin rewording a milestone cannot rewrite
 * what a parent already saved. An entry is written only on submit, which is
 * what makes "this milestone is done" a single EXISTS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('months_from')->nullable();
            $table->unsignedSmallInteger('xp')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_editable')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['child_id', 'sort_order']);
        });

        Schema::create('child_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('months_from')->nullable();
            $table->unsignedSmallInteger('xp')->default(25);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_editable')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['child_chapter_id', 'sort_order']);
            $table->index(['child_id', 'is_hidden']);
        });

        Schema::create('child_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            // Unique, and nullable so the free memories — which have no milestone —
            // can repeat: MySQL does not collide on repeated NULLs.
            $table->foreignId('child_milestone_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->date('date');
            $table->string('mood')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['child_id', 'date']);
            $table->index(['child_id', 'created_at']);
        });

        Schema::create('child_milestone_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_milestone_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

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

        // The trophy is copied, not joined: retuning a threshold or renaming a
        // trophy must not rewrite one a child has already been shown. trophy_id
        // stays only to stop the same trophy being awarded twice.
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

        Schema::create('child_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_trophy_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('unclaimed');
            $table->longText('content')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_rewards');
        Schema::dropIfExists('child_trophies');
        Schema::dropIfExists('child_entry_properties');
        Schema::dropIfExists('child_milestone_properties');
        Schema::dropIfExists('child_entries');
        Schema::dropIfExists('child_milestones');
        Schema::dropIfExists('child_chapters');
    }
};
