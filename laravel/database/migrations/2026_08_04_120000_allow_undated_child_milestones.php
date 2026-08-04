<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_milestones', function (Blueprint $table) {
            $table->unsignedSmallInteger('happens_after')->nullable()->change();
            $table->string('happens_unit')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('child_milestones')->whereNull('happens_after')->update([
            'happens_after' => 0,
            'happens_unit' => 'days',
        ]);

        Schema::table('child_milestones', function (Blueprint $table) {
            $table->unsignedSmallInteger('happens_after')->default(0)->nullable(false)->change();
            $table->string('happens_unit')->default('days')->nullable(false)->change();
        });
    }
};
