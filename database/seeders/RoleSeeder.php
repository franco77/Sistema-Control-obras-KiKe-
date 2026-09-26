<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos del panel.
 *
 * El rol «admin» no necesita permisos explícitos: AppServiceProvider le
 * concede todo mediante Gate::before. Los demás se definen aquí.
 */
class RoleSeeder extends Seeder
{
    /** Módulos con sus acciones. */
    private const MODULES = [
        'clients' => ['view', 'create', 'update', 'delete'],
        'providers' => ['view', 'create', 'update', 'delete'],
        'quotes' => ['view', 'create', 'update', 'delete', 'send'],
        'projects' => ['view', 'create', 'update', 'delete', 'financials'],
        'calendar' => ['view', 'create', 'update', 'delete'],
        'catalog' => ['view', 'create', 'update', 'delete'],
        'documents' => ['view', 'create', 'update', 'delete'],
        'conversations' => ['view', 'update'],
        'settings' => ['manage'],
    ];

    private const ROLES = [
        'admin' => ['*'],

        'jefe de obra' => [
            'clients.view', 'providers.view', 'quotes.view',
            'projects.view', 'projects.update',
            'calendar.view', 'calendar.create', 'calendar.update', 'calendar.delete',
            'documents.view', 'documents.create', 'documents.update',
            'conversations.view', 'conversations.update',
            'catalog.view',
        ],

        'comercial' => [
            'clients.view', 'clients.create', 'clients.update',
            'quotes.view', 'quotes.create', 'quotes.update', 'quotes.send', 'quotes.delete',
            'projects.view',
            'calendar.view', 'calendar.create', 'calendar.update',
            'catalog.view',
            'documents.view', 'documents.create',
            'conversations.view', 'conversations.update',
        ],

        'administrativo' => [
            'clients.view', 'clients.update',
            'providers.view', 'providers.create', 'providers.update',
            'quotes.view', 'projects.view',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            'calendar.view',
            'conversations.view', 'conversations.update',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$module}.{$action}");
            }
        }

        foreach (self::ROLES as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName);

            $role->syncPermissions(
                $permissions === ['*'] ? Permission::all() : $permissions
            );
        }
    }
}