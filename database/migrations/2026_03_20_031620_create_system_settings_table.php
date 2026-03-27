<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('avatar_size')->default(100);
            $table->unsignedTinyInteger('avatar_quality')->default(80);
            $table->string('avatar_format', 10)->default('webp');
            $table->unsignedSmallInteger('max_upload_size_mb')->default(2);
            $table->unsignedSmallInteger('default_per_page')->default(10);
            $table->unsignedTinyInteger('password_min_length')->default(12);
            $table->boolean('password_require_mixed_case')->default(true);
            $table->boolean('password_require_numbers')->default(true);
            $table->boolean('password_require_symbols')->default(true);
            $table->boolean('password_require_uncompromised')->default(true);
            $table->unsignedTinyInteger('login_rate_limit')->default(5);
            $table->boolean('form_rate_limit_enabled')->default(true);
            $table->unsignedTinyInteger('form_edit_rate_limit')->default(10);
            $table->unsignedTinyInteger('form_action_rate_limit')->default(5);
            $table->unsignedSmallInteger('password_reset_expiry_minutes')->default(60);
            $table->unsignedSmallInteger('session_lifetime_minutes')->default(120);
            $table->timestamp('calendar_config_updated_at')->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
