<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 10)->default('ud');       // App\Enums\MeasurementUnit
            $table->decimal('unit_cost', 12, 4)->default(0); // coste interno
            $table->decimal('unit_price', 12, 4)->default(0);// PVP recomendado
            $table->decimal('default_quantity', 12, 3)->default(1);
            $table->decimal('yield_per_day', 10, 3)->nullable(); // rendimiento (ud/día)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['catalog_category_id', 'name']);
            $table->index('trade_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
