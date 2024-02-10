<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('backup_configuration_files', function (
            Blueprint $table
        ) {
            $table
                ->foreignId('backup_configuration_id')
                ->constrained('backup_configurations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table
                ->foreignId('file_id')
                ->constrained('files')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('last_backup_checksum', 32)->nullable();
            $table->timestamp('last_backup_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();

            $table->primary(['backup_configuration_id', 'file_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('backup_configuration_files');
    }
};
