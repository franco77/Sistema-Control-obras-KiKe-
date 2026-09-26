<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('related');              // Project, Quote…
            $table->string('channel', 20)->default('mail'); // mail|whatsapp|sms
            $table->string('recipient');
            $table->string('recipient_type', 20)->default('client');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('queued'); // queued|sent|failed|opened
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
