<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create extension if not exists postgis');

        Schema::create('lines', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('code')->index();
            $t->string('name');
            $t->string('agency');
        });

        Schema::create('stops', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
            $t->geography('geom', 'point', 4326);
            $t->spatialIndex('geom');
        });

        Schema::create('line_stop', function (Blueprint $t) {
            $t->string('line_id');
            $t->string('stop_id')->index();
            $t->primary(['line_id', 'stop_id']);
            $t->foreign('line_id')->references('id')->on('lines')->cascadeOnDelete();
            $t->foreign('stop_id')->references('id')->on('stops')->cascadeOnDelete();
        });

        Schema::create('paths', function (Blueprint $t) {
            $t->id();
            $t->string('line_id')->index();
            $t->smallInteger('dir');
            $t->geography('geom', 'linestring', 4326);
            $t->foreign('line_id')->references('id')->on('lines')->cascadeOnDelete();
        });

        Schema::create('syncs', function (Blueprint $t) {
            $t->id();
            $t->string('version');
            $t->string('tag')->nullable();
            $t->json('added');
            $t->json('removed');
            $t->unsignedInteger('total');
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach (['syncs', 'paths', 'line_stop', 'stops', 'lines'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
