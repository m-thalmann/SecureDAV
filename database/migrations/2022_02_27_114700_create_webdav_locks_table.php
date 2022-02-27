<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create("webdav_locks", function (Blueprint $table) {
            $table->id();
            $table->string("owner", 100);
            $table->unsignedInteger("timeout");
            $table->integer("created");
            $table->tinyInteger("scope");
            $table->tinyInteger("depth");
            $table->text("token", 100);
            $table->text("uri", 1000);
            $table->unique([DB::raw("token(100)")]);
            $table->index([DB::raw("uri(100)")]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists("webdav_locks");
    }
};
