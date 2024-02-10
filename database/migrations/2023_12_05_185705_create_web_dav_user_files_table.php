<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('web_dav_user_files', function (Blueprint $table) {
            $table
                ->foreignId('web_dav_user_id')
                ->constrained('web_dav_users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table
                ->foreignId('file_id')
                ->constrained('files')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->primary(['web_dav_user_id', 'file_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('web_dav_user_files');
    }
};
