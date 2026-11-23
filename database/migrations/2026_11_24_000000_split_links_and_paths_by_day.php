<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('paths');
        Schema::dropIfExists('line_stop');

        Schema::table('lines', function (Blueprint $t) {
            $t->unsignedSmallInteger('days')->default(0);
        });

        Schema::create('line_stop', function (Blueprint $t) {
            $t->string('line_id');
            $t->string('stop_id')->index();
            $t->smallInteger('dir');
            $t->unsignedSmallInteger('seq');
            $t->unsignedSmallInteger('days');
            $t->primary(['line_id', 'dir', 'stop_id', 'seq']);
            $t->foreign('line_id')->references('id')->on('lines')->cascadeOnDelete();
            $t->foreign('stop_id')->references('id')->on('stops')->cascadeOnDelete();
        });

        Schema::create('paths', function (Blueprint $t) {
            $t->id();
            $t->string('line_id')->index();
            $t->smallInteger('dir');
            $t->unsignedSmallInteger('days');
            $t->geography('geom', 'linestring', 4326);
            $t->foreign('line_id')->references('id')->on('lines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paths');
        Schema::dropIfExists('line_stop');

        Schema::table('lines', function (Blueprint $t) {
            $t->dropColumn('days');
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
    }
};
