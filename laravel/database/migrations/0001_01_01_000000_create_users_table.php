<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The account.
 *
 * Email and password are nullable: a device registers itself on first launch and
 * the parent decides later whether to put a name to it, so an account exists long
 * before anybody has typed an address.
 *
 * The streak lives here rather than on the child — it is the parent's habit of
 * showing up, and it should not reset because they opened a second child's map.
 *
 * share_code is how one account names another out loud: the six characters a
 * grandparent is read over the phone and types in to be let into a child's map.
 * It exists because email cannot do that job — an account is created by a device
 * before anybody has typed an address, so most of them have none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('share_code', 6)->unique();
            $table->string('password')->nullable();
            $table->string('language', 2)->default('en');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_admin')->default(false);
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('longest_streak')->default(0);
            $table->date('last_entry_date')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
