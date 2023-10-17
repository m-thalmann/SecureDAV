<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('access_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('label')->nullable();
            $table->string('password');
            $table->boolean('active');
            $table->boolean('readonly');
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('access_users');
    }
};

