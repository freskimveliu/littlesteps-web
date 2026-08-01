<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin catalogue: shared by every family, copied onto a child at provisioning.
 *
 * These tables soft-delete because a child's rows keep a template_*_id pointing
 * back here; removing a step outright would break that trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon');
            $table->string('color', 9);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('template_milestones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon');
            $table->unsignedSmallInteger('months_from')->nullable();
            $table->unsignedSmallInteger('xp')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_editable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('months_from');
        });

        Schema::create('template_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_milestone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('months_from')->nullable();
            $table->unsignedSmallInteger('xp')->default(25);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_editable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['template_milestone_id', 'sort_order']);
        });

        Schema::create('template_step_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_step_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('template_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon');
            $table->unsignedInteger('min_xp')->unique();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('template_achievements', function (Blueprint $table) {
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

        Schema::create('template_prompts', function (Blueprint $table) {
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

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('template_prompts');
        Schema::dropIfExists('template_achievements');
        Schema::dropIfExists('template_levels');
        Schema::dropIfExists('template_step_properties');
        Schema::dropIfExists('template_steps');
        Schema::dropIfExists('template_milestones');
        Schema::dropIfExists('categories');
    }
};
