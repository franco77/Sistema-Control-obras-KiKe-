<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('status', 20)->default('open');  // App\Enums\ConversationStatus
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_for_staff')->default(0);
            $table->unsignedInteger('unread_for_client')->default(0);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
