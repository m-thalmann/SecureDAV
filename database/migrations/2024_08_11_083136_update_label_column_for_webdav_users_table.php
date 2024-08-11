<?php

use App\Models\WebDavUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('web_dav_users', function (Blueprint $table) {
            WebDavUser::query()
                ->where('label', null)
                ->get()
                ->each(function (WebDavUser $webDavUser) {
                    $userId = substr($webDavUser->username, 0, 8);
                    $webDavUser->update(['label' => "user-{$userId}"]);
                });

            $table
                ->text('label')
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void {
        Schema::table('web_dav_users', function (Blueprint $table) {
            $table
                ->string('label')
                ->nullable()
                ->change();
        });
    }
};
