<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('web_dav_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('username')->unique();
            $table->string('password');
            $table->string('label')->nullable();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->boolean('active');
            $table->boolean('readonly');
            $table->timestamp('last_access')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('web_dav_users');
    }
};
