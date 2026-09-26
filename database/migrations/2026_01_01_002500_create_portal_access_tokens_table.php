<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');                    // Project, Quote, ProjectExtra, Provider
            $table->string('name')->nullable();
            $table->string('token_hash', 64)->unique();     // sha256 del token en claro
            $table->string('audience', 20)->default('client'); // client|provider
            $table->json('abilities')->nullable();          // ["project.view","extra.approve"]
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->unsignedInteger('uses')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['audience', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_access_tokens');
    }
};
