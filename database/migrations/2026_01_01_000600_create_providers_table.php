<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // PRV-2026-0001
            $table->string('type', 30)->default('freelancer'); // App\Enums\ProviderType
            $table->string('status', 30)->default('pending_docs');
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('iban', 34)->nullable();
            $table->decimal('default_hourly_rate', 8, 2)->nullable();
            $table->decimal('irpf_rate', 5, 2)->nullable();    // retención aplicable
            $table->unsignedTinyInteger('max_parallel_projects')->default(3);
            $table->decimal('rating', 3, 2)->nullable();       // media de valoraciones
            $table->unsignedSmallInteger('jobs_count')->default(0);
            $table->unsignedSmallInteger('radius_km')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'name']);
            $table->index('tax_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
