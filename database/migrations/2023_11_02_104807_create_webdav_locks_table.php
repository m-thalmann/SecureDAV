<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('webdav_locks', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('owner', 100);
            $table->unsignedInteger('timeout');
            $table->integer('created');
            $table->string('token', 100);
            $table->tinyInteger('scope');
            $table->tinyInteger('depth');
            $table->string('uri', 750);
            $table->index('token');
            $table->index('uri');
            $table->unique(['user_id', 'token']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('webdav_locks');
    }
};
