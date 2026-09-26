<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingSeeder::class,
            TradeSeeder::class,
            CatalogSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'position' => 'Dirección',
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['admin']);

        foreach ([
            ['Laura Jefa de Obra', 'obra@crm.test', 'jefe de obra', 'Jefatura de obra'],
            ['Marco Comercial', 'comercial@crm.test', 'comercial', 'Comercial'],
            ['Ana Administración', 'admin.oficina@crm.test', 'administrativo', 'Administración'],
        ] as [$name, $email, $role, $position]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'position' => $position, 'email_verified_at' => now()],
            );

            $user->syncRoles([$role]);
        }

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}