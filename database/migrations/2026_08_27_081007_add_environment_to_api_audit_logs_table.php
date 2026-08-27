<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Target the primary PostgreSQL connection.
     */
    protected $connection = 'pgsql_main';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql_main')->table('api_audit_logs', function (Blueprint $table) {
            $table->string('environment', 20)->default('sandbox')->after('id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_main')->table('api_audit_logs', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }
};
