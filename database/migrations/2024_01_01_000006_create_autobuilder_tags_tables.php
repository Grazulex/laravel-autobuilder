<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autobuilder_tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('autobuilder_flow_tag', function (Blueprint $table) {
            $table->ulid('flow_id');
            $table->ulid('tag_id');
            $table->primary(['flow_id', 'tag_id']);
            $table->foreign('flow_id')->references('id')->on('autobuilder_flows')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('autobuilder_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autobuilder_flow_tag');
        Schema::dropIfExists('autobuilder_tags');
    }
};
