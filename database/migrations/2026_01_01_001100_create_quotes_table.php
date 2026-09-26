<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30);                      // PRE-2026-0042
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('project_id')->nullable(); // FK en migración posterior
            $table->foreignId('parent_quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 30)->default('draft');    // App\Enums\QuoteStatus

            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('estimated_duration_days')->nullable();

            // Importes: se recalculan siempre en QuoteCalculator, nunca a mano.
            $table->decimal('items_total', 14, 2)->default(0);
            $table->string('discount_type', 20)->default('none');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('taxable_base', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(21);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('cost_total', 14, 2)->default(0);  // coste interno estimado
            $table->decimal('margin_amount', 14, 2)->default(0);
            $table->decimal('margin_percent', 6, 2)->default(0);

            $table->text('payment_terms')->nullable();
            $table->text('terms')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('internal_notes')->nullable();

            // Circuito de aprobación por el cliente
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_ip', 45)->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Las versiones de un mismo presupuesto comparten numero.
            $table->unique(['number', 'version']);

            $table->index(['client_id', 'status']);
            $table->index(['status', 'issue_date']);
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
