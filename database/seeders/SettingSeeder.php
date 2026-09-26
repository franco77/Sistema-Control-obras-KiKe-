<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['company.name', 'Reformas Ejemplo S.L.', 'string', 'empresa'],
            ['company.legal_name', 'Reformas Ejemplo, S.L.', 'string', 'empresa'],
            ['company.tax_id', 'B00000000', 'string', 'empresa'],
            ['company.address', 'C/ Mayor 1, 28013 Madrid', 'string', 'empresa'],
            ['company.phone', '910 000 000', 'string', 'empresa'],
            ['company.email', 'hola@reformasejemplo.test', 'string', 'empresa'],
            ['company.tax_rate', '21', 'float', 'empresa'],

            ['quotes.prefix', 'PRE', 'string', 'presupuestos'],
            ['quotes.valid_days', '30', 'int', 'presupuestos'],
            ['quotes.token_ttl_days', '120', 'int', 'presupuestos'],
            ['quotes.payment_terms', "30 % a la firma del presupuesto.\n40 % al inicio de los trabajos.\n30 % a la entrega de la obra.", 'string', 'presupuestos'],
            ['quotes.terms', "Los precios incluyen mano de obra, materiales y retirada de escombros salvo indicación contraria.\nCualquier trabajo no recogido en este documento se presupuestará aparte y requerirá su aprobación por escrito.", 'string', 'presupuestos'],

            ['projects.token_ttl_days', '365', 'int', 'obras'],
            ['providers.require_documents', '1', 'bool', 'obras'],
        ];

        foreach ($defaults as [$key, $value, $cast, $group]) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'cast' => $cast, 'group' => $group],
            );
        }
    }
}