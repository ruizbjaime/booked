<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('last_login_at');
            $table->foreignId('document_type_id')->nullable()->after('phone')
                ->constrained('identification_document_types')->nullOnDelete();
            $table->string('document_number')->nullable()->after('document_type_id');
            $table->foreignId('country_id')->nullable()->after('document_number')
                ->constrained('countries')->nullOnDelete();
            $table->string('state')->nullable()->after('country_id');
            $table->string('city')->nullable()->after('state');
            $table->text('address')->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_type_id');
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn(['phone', 'document_number', 'state', 'city', 'address']);
        });
    }
};
