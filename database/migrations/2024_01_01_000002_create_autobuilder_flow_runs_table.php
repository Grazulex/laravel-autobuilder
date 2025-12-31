<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autobuilder_flow_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('flow_id')->constrained('autobuilder_flows')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, running, completed, failed, paused
            $table->json('payload')->nullable();
            $table->json('variables')->nullable();
            $table->json('logs')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['flow_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autobuilder_flow_runs');
    }
};
