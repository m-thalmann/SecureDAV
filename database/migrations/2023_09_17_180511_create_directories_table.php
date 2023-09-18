<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('directories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table
                ->foreignId('parent_directory_id')
                ->nullable()
                ->constrained('directories')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['directory_id', 'name']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('directories');
    }
};

