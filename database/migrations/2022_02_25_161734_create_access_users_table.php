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
        Schema::create("access_users", function (Blueprint $table) {
            $table->id();
            $table->string("username")->unique();
            $table->string("label")->nullable();
            $table->boolean("readonly");
            $table->boolean("access_all");
            $table
                ->foreignId("user_id")
                ->constrained("users")
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists("access_users");
    }
};
