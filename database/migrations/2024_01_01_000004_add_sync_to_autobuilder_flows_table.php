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
            $table->boolean('sync')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('autobuilder_flows', function (Blueprint $table) {
            $table->dropColumn('sync');
        });
    }
};
