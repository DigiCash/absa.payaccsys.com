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
        Schema::connection('pgsql_main')->create('api_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('correlation_id')->index();
            $table->string('direction', 30)->index(); // inbound_facade | outbound_absa
            $table->string('service', 50)->index();   // e.g., 'statements'
            $table->string('method', 10);             // GET, POST, etc.
            $table->text('endpoint');
            $table->jsonb('request_headers')->nullable();
            $table->jsonb('request_payload')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable()->index();
            $table->jsonb('response_payload')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->jsonb('exception_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_main')->dropIfExists('api_audit_logs');
    }
};
