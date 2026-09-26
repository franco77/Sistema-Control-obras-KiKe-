<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_trade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'trade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_trade');
    }
};
