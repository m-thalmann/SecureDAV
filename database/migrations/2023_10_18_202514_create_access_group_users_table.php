<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('access_group_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('username')->unique();
            $table
                ->foreignId('access_group_id')
                ->constrained('access_groups')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('password');
            $table->string('label')->nullable();
            $table->timestamp('last_access')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('access_group_users');
    }
};

