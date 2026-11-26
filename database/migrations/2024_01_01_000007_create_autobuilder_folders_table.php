<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autobuilder_folders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->ulid('parent_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('autobuilder_flows', function (Blueprint $table): void {
            // Flows without a folder stay "uncategorized" (null)
            $table->ulid('folder_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('autobuilder_flows', function (Blueprint $table): void {
            $table->dropIndex(['folder_id']);
        });

        Schema::table('autobuilder_flows', function (Blueprint $table): void {
            $table->dropColumn('folder_id');
        });

        Schema::dropIfExists('autobuilder_folders');
    }
};
