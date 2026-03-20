<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('en_label')->nullable()->after('guard_name');
            $table->string('es_label')->nullable()->after('en_label');
            $table->string('color', 30)->default('zinc')->after('es_label');
            $table->unsignedInteger('sort_order')->default(999)->after('color');
            $table->boolean('is_active')->default(true)->after('sort_order');
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        $defaultRole = config('roles.default_role', 'guest');

        DB::table('roles')
            ->where('guard_name', 'web')
            ->where('name', $defaultRole)
            ->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['en_label', 'es_label', 'color', 'sort_order', 'is_active', 'is_default']);
        });
    }
};
