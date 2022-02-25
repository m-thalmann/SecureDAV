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
        Schema::create("files", function (Blueprint $table) {
            $table->uuid("uuid")->primary();
            $table
                ->foreignId("user_id")
                ->constrained("users")
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string("display_name")->nullable();
            $table->string("client_name");
            $table->string("mime_type");
            $table->string("extension");
            $table->boolean("encrypted");
            $table->timestamps();
            $table->unique(["user_id", "display_name"]);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists("files");
    }
};
