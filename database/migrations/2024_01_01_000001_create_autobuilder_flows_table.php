<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autobuilder_flows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('nodes')->nullable();
            $table->json('edges')->nullable();
            $table->json('viewport')->nullable();
            $table->string('webhook_path')->nullable()->unique();
            $table->boolean('active')->default(false);
            $table->string('trigger_type')->nullable()->index();
            $table->json('trigger_config')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autobuilder_flows');
    }
};
