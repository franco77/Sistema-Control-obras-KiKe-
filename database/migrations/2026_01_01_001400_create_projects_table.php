<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // OBR-2026-0007
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft');    // App\Enums\ProjectStatus
            $table->unsignedTinyInteger('progress')->default(0); // 0-100, recalculado

            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->unsignedSmallInteger('warranty_months')->default(12);

            $table->decimal('budget_total', 14, 2)->default(0);   // venta contratada
            $table->decimal('extras_total', 14, 2)->default(0);   // extras aprobados
            $table->decimal('cost_estimated', 14, 2)->default(0);
            $table->decimal('cost_real', 14, 2)->default(0);

            $table->boolean('portal_enabled')->default(true);
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'planned_start']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
