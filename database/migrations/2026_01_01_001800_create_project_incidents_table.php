<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // INC-2026-0015
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_phase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('severity', 20)->default('medium'); // App\Enums\IncidentSeverity
            $table->string('status', 30)->default('open');     // App\Enums\IncidentStatus

            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->date('due_date')->nullable();
            $table->text('resolution')->nullable();

            $table->decimal('cost_impact', 12, 2)->default(0);
            $table->unsignedSmallInteger('days_impact')->default(0);
            $table->boolean('visible_to_client')->default(false);
            $table->boolean('reported_by_client')->default(false);

            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_incidents');
    }
};
