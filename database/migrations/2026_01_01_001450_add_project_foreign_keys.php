<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        Schema::table('provider_availabilities', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        Schema::table('provider_availabilities', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
    }
};
