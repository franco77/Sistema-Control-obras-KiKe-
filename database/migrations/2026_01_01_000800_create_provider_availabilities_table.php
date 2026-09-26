<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('unavailable'); // App\Enums\AvailabilityType
            $table->date('starts_on');
            $table->date('ends_on');
            $table->time('starts_at')->nullable();              // null = todo el día
            $table->time('ends_at')->nullable();
            $table->foreignId('project_id')->nullable();        // FK añadida tras crear projects
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_availabilities');
    }
};
