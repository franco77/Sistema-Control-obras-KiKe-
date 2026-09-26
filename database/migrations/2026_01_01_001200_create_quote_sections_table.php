<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                       // "Capítulo 1 - Demolición"
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('cost_subtotal', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['quote_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_sections');
    }
};
