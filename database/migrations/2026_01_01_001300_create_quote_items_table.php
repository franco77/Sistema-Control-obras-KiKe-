<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 10)->default('ud');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 12, 4)->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('cost_total', 14, 2)->default(0);
            $table->boolean('is_optional')->default(false);   // partida opcional para el cliente
            $table->boolean('is_included')->default(true);    // si es opcional, si la acepta
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['quote_section_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
