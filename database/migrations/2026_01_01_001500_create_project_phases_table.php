<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                            // Demolición, Fontanería…
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending');  // App\Enums\PhaseStatus
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedTinyInteger('weight')->default(1); // peso en el % global
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->decimal('budget_amount', 14, 2)->default(0);
            $table->boolean('visible_to_client')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'position']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }
};
