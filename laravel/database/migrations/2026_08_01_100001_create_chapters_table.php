<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The guided chapters, in age order.
 *
 * Soft-deleted rather than removed: a child's own chapters keep a chapter_id
 * pointing back here, and taking one out from under them would break that trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
