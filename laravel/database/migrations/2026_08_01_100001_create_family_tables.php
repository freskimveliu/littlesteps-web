<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The account and who it shares a child with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable so a user can exist from first launch and fill these in later.
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            $table->string('language', 2)->default('en')->after('password');
            $table->string('timezone')->default('UTC')->after('language');
            $table->boolean('is_admin')->default(false)->after('timezone');
            $table->unsignedSmallInteger('current_streak')->default(0)->after('is_admin');
            $table->unsignedSmallInteger('longest_streak')->default(0)->after('current_streak');
            $table->date('last_entry_date')->nullable()->after('longest_streak');
            $table->softDeletes();
        });

        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->date('birthday');
            $table->string('gender');
            $table->unsignedInteger('xp')->default(0);
            $table->timestamps();
        });

        Schema::create('child_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('relation');
            $table->string('role');
            $table->timestamps();

            $table->unique(['child_id', 'user_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->morphs('settable');
            $table->string('key');
            $table->string('value');
            $table->timestamps();

            $table->unique(['settable_type', 'settable_id', 'key']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('push_token')->unique();
            $table->string('platform');
            $table->string('device_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('child_members');
        Schema::dropIfExists('children');

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'language', 'timezone', 'is_admin',
                'current_streak', 'longest_streak', 'last_entry_date',
            ]);
        });
    }
};
