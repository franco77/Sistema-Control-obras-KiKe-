<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('position')->nullable()->after('phone');
            $table->string('avatar_path')->nullable()->after('position');
            $table->string('color', 20)->nullable()->after('avatar_path');
            $table->boolean('is_active')->default(true)->after('color');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'position', 'avatar_path', 'color',
                'is_active', 'last_login_at', 'deleted_at',
            ]);
        });
    }
};
