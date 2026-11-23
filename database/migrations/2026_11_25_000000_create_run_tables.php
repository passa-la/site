<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patterns', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('line_id');
            $t->smallInteger('dir');
            $t->index(['line_id', 'dir']);
            $t->foreign('line_id')->references('id')->on('lines')->cascadeOnDelete();
        });

        Schema::create('pattern_stop', function (Blueprint $t) {
            $t->integer('pattern_id');
            $t->unsignedSmallInteger('pos');
            $t->string('stop_id')->index();
            $t->primary(['pattern_id', 'pos']);
            $t->foreign('pattern_id')->references('id')->on('patterns')->cascadeOnDelete();
        });

        Schema::create('runs', function (Blueprint $t) {
            $t->id();
            $t->integer('pattern_id')->index();
            $t->unsignedSmallInteger('days');
            $t->foreign('pattern_id')->references('id')->on('patterns')->cascadeOnDelete();
        });

        DB::statement('alter table runs add column times integer[] not null');
    }

    public function down(): void
    {
        foreach (['runs', 'pattern_stop', 'patterns'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
