<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_phase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->unsignedTinyInteger('progress_snapshot')->nullable();
            $table->boolean('visible_to_client')->default(true);
            $table->boolean('notify_client')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_updates');
    }
};
