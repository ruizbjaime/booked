<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('form_rate_limit_enabled')->default(true)->after('login_rate_limit');
            $table->unsignedTinyInteger('form_edit_rate_limit')->default(10)->after('form_rate_limit_enabled');
            $table->unsignedTinyInteger('form_action_rate_limit')->default(5)->after('form_edit_rate_limit');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['form_rate_limit_enabled', 'form_edit_rate_limit', 'form_action_rate_limit']);
        });
    }
};
