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
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('mime_type');
            $table->string('extension');
            $table->boolean('encrypted'); // TODO: add encryption key per file
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['directory_id', 'name']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('files');
    }
};

