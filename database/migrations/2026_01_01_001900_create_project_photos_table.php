<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_phase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_incident_id')->nullable()->constrained()->nullOnDelete();

            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            $table->string('stage', 20)->default('progress'); // App\Enums\PhotoStage
            $table->string('caption')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->boolean('visible_to_client')->default(true);
            $table->boolean('is_cover')->default(false);

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('uploaded_by_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'visible_to_client']);
            $table->index(['project_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_photos');
    }
};
