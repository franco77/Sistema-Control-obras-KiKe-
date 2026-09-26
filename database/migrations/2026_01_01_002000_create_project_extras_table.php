<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_extras', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // EXT-2026-0003
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_phase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_incident_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description');
            $table->text('justification')->nullable();
            $table->string('status', 30)->default('draft');    // App\Enums\ExtraStatus

            $table->decimal('amount', 14, 2)->default(0);      // PVP sin IVA
            $table->decimal('tax_rate', 5, 2)->default(21);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('cost_estimated', 14, 2)->default(0);
            $table->unsignedSmallInteger('extra_days')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_ip', 45)->nullable();
            $table->string('signer_name')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_extras');
    }
};
