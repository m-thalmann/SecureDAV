<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('access_user_files', function (Blueprint $table) {
            $table
                ->foreignId('access_user_id')
                ->constrained('access_users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table
                ->foreignId('file_id')
                ->constrained('files')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->primary(['access_user_id', 'file_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('access_user_files');
    }
};

