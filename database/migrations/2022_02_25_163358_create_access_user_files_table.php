<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create("access_user_files", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("access_user_id")
                ->constrained("access_users")
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table
                ->foreignUuid("file_uuid")
                ->constrained("files", "uuid")
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unique(["access_user_id", "file_uuid"]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists("access_user_files");
    }
};
