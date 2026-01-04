<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autobuilder_flows', function (Blueprint $table) {
            // Index for filtering active flows (frequently queried)
            $table->index('active');

            // Index for soft delete queries
            $table->index('deleted_at');

            // Composite index for listing flows by user
            $table->index(['created_by', 'created_at']);
        });

        Schema::table('autobuilder_flow_runs', function (Blueprint $table) {
            // Index for status-only queries
            $table->index('status');

            // Index for time-range queries
            $table->index('started_at');
            $table->index('completed_at');

            // Composite index for recent runs by flow
            $table->index(['flow_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('autobuilder_flows', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['created_by', 'created_at']);
        });

        Schema::table('autobuilder_flow_runs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['started_at']);
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['flow_id', 'created_at']);
        });
    }
};
