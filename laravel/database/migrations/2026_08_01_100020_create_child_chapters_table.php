<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The child's own journey begins here.
 *
 * Chapters are copied from the catalogue at provisioning and never joined back at
 * read time, so an admin rewording one cannot rewrite what a parent already saved.
 * chapter_id is a trail back to where the row came from, nothing more.
 *
 * completed_at is the seal: a finished chapter has given its gift and its shape
 * stops changing.
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
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['child_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_chapters');
    }
};
