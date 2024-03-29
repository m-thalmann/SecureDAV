<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('backup_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('provider_class');
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('label');
            $table->boolean('store_with_version');
            $table->text('config')->nullable();
            $table->string('cron_schedule')->nullable();
            $table->boolean('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('backup_configurations');
    }
};
