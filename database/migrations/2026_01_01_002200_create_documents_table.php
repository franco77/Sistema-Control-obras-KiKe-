<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // Client, Property, Provider, Project, Quote…
            $table->string('category', 40)->default('other'); // App\Enums\DocumentCategory
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('hash', 64)->nullable();           // sha256, evita duplicados
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();           // seguros, PRL, RETA…
            $table->boolean('is_required')->default(false);
            $table->boolean('visible_to_client')->default(false);
            $table->boolean('visible_to_provider')->default(false);
            $table->timestamp('expiry_notified_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'expires_on']);
            $table->index('expires_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
