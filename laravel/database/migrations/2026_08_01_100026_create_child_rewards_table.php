<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The gift behind a trophy. Reserved unclaimed at unlock and generated only when
 * the parent asks for it, so nothing is written that nobody opens.
 */
return new class extends Migration
{
    public function up(): void
    {
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
    }
};
