<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('response', 20)->default('pending'); // pending|accepted|declined
            $table->timestamps();

            $table->unique(['calendar_event_id', 'user_id'], 'calendar_event_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_user');
    }
};
