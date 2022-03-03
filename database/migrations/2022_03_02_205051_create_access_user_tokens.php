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
        Schema::create("access_user_tokens", function (Blueprint $table) {
            $table->id();
            $table->string("token");
            $table
                ->foreignId("access_user_id")
                ->constrained("access_users")
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->boolean("active")->default(true);
            $table->timestamp("last_access")->nullable();
            $table->string("label")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists("access_user_tokens");
    }
};
