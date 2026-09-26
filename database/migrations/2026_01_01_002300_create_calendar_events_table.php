<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 30)->default('visit');      // App\Enums\EventType
            $table->string('status', 30)->default('scheduled'); // App\Enums\EventStatus

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('color', 20)->nullable();

            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_phase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('reminder_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
            $table->index(['type', 'status']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
