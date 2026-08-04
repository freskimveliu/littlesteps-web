<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who a child is shared with.
 *
 * `relation` is how they answer "who are you to this child" — mother, grandparent,
 * aunt — and is theirs alone: two people may both be a grandparent. `role` is what
 * the app lets them do, and there are two of them: a viewer reads the map, an
 * editor may add memories to it. Neither can rename or delete the child; that
 * stays with children.created_by_user_id.
 *
 * A row per pairing, unique on the pair, so an invitation accepted twice cannot
 * hand somebody two different roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('relation');
            $table->string('role');
            $table->timestamps();

            $table->unique(['child_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_members');
    }
};
