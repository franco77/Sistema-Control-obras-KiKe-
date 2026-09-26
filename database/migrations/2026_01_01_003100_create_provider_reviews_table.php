<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('quality')->default(5);
            $table->unsignedTinyInteger('punctuality')->default(5);
            $table->unsignedTinyInteger('tidiness')->default(5);
            $table->unsignedTinyInteger('communication')->default(5);
            $table->decimal('score', 3, 2)->default(5);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'project_id'], 'provider_review_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_reviews');
    }
};
