<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table
                ->foreignId('directory_id')
                ->nullable()
                ->constrained('directories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('encryption_key', 16)->nullable();
            $table->unsignedFloat('auto_version_hours', 4, 1)->nullable();
            $table->integer('next_version');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('files');
    }
};

