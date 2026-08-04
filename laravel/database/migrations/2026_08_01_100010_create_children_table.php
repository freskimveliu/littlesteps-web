<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The child.
 *
 * created_by_user_id is ownership, not membership: only that account may rename or
 * remove the child. Everybody else reaches the child through child_members.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->date('birthday');
            $table->string('gender');
            $table->unsignedInteger('xp')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
