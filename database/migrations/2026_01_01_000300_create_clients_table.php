<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // CLI-2026-0001
            $table->string('type', 30)->default('individual'); // App\Enums\ClientType
            $table->string('status', 30)->default('lead');     // App\Enums\ClientStatus
            $table->string('name');                            // nombre comercial / particular
            $table->string('legal_name')->nullable();          // razón social
            $table->string('tax_id', 30)->nullable();          // NIF / CIF / NIE
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_alt', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('address_extra')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country', 2)->default('ES');
            $table->string('source', 60)->nullable();          // web, recomendación, campaña…
            $table->string('preferred_channel', 20)->default('email');
            $table->boolean('accepts_marketing')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'name']);
            $table->index('tax_id');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
