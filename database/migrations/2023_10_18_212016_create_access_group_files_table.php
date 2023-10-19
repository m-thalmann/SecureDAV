<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('access_group_files', function (Blueprint $table) {
            $table
                ->foreignId('access_group_id')
                ->constrained('access_groups')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table
                ->foreignId('file_id')
                ->constrained('files')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->primary(['access_group_id', 'file_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('access_group_files');
    }
};

