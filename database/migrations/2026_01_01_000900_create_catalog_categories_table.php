<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('catalog_categories')->nullOnDelete();
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_categories');
    }
};
