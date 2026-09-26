<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_phase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending');  // App\Enums\TaskStatus
            $table->string('priority', 20)->default('normal'); // App\Enums\TaskPriority
            $table->unsignedSmallInteger('position')->default(0);

            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->decimal('cost_estimated', 12, 2)->default(0);
            $table->decimal('cost_real', 12, 2)->default(0);

            $table->boolean('visible_to_client')->default(true);
            $table->boolean('requires_client_approval')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_phase_id', 'position']);
            $table->index(['provider_id', 'status']);
            $table->index(['planned_start', 'planned_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
