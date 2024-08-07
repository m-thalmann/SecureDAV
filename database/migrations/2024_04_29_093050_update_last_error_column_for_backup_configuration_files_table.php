<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('backup_configuration_files', function (
            Blueprint $table
        ) {
            $table
                ->text('last_error')
                ->nullable()
                ->change();
        });
    }

    public function down(): void {
        Schema::table('backup_configuration_files', function (
            Blueprint $table
        ) {
            $table
                ->string('last_error')
                ->nullable()
                ->change();
        });
    }
};
