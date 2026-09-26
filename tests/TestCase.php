<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TradeSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Datos maestros mínimos: roles, configuración y oficios. */
    protected function seedBaseline(): void
    {
        $this->seed([RoleSeeder::class, SettingSeeder::class, TradeSeeder::class]);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}