<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('alias');                           // "Piso Chamberí"
            $table->string('type', 30)->default('apartment');  // App\Enums\PropertyType
            $table->string('address');
            $table->string('block', 20)->nullable();
            $table->string('floor', 20)->nullable();
            $table->string('door', 20)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('cadastral_reference', 30)->nullable();
            $table->decimal('built_area', 8, 2)->nullable();   // m² construidos
            $table->decimal('usable_area', 8, 2)->nullable();  // m² útiles
            $table->unsignedSmallInteger('rooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->boolean('has_elevator')->default(false);
            $table->boolean('is_occupied')->default(true);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('access_notes')->nullable();          // portero, llaves, horarios
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'alias']);
            $table->index('postal_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
