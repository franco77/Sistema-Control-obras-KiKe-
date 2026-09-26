<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role', 80)->nullable();   // administrador, presidente, arquitecto…
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('receives_notifications')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};
