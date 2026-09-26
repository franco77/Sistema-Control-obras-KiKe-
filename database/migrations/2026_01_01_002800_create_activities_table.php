<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');                      // Quote, Project, ProjectExtra…
            $table->nullableMorphs('causer');               // User, o null si es el cliente
            $table->string('event', 60);                    // quote.sent, extra.approved…
            $table->string('description')->nullable();
            $table->json('properties')->nullable();         // diff / metadatos
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
