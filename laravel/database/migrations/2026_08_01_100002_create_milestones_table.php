<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The guided milestones.
 *
 * months_from gates when one opens on the map. happens_after and its unit say
 * when it actually occurs, which is what dates a memory and where the photo
 * gallery opens. is_date_editable is false for the ones that *are* a date
 * — "Month 5", "Fourth Birthday" — which land on their exact day and may not be
 * rehomed or shuffled; a first ("First Haircut") moves freely.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('happens_after')->default(0);
            $table->string('happens_unit')->default('days');
            $table->boolean('is_date_editable')->default(true);
            $table->unsignedSmallInteger('xp')->default(25);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_editable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['chapter_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
