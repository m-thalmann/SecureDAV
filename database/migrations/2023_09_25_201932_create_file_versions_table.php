<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('file_id')
                ->constrained('files')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->integer('version');
            $table->string('storage_path');
            $table->string('etag', 32);
            $table->unsignedBigInteger('bytes');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['file_id', 'version']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('file_versions');
        // TODO: delete files from storage
    }
};

