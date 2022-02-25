<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create("file_versions", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignUuid("file_uuid")
                ->constrained("files", "uuid")
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer("version");
            $table->string("path");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists("file_versions");
        Storage::disk("files")->delete(Storage::disk("files")->files());
    }
};
