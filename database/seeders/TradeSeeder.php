<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Trade;
use Illuminate\Database\Seeder;

class TradeSeeder extends Seeder
{
    private const TRADES = [
        ['Demolición', 'gray'],
        ['Albañilería', 'orange'],
        ['Fontanería', 'blue'],
        ['Electricidad', 'amber'],
        ['Climatización', 'teal'],
        ['Carpintería de madera', 'orange'],
        ['Carpintería de aluminio', 'gray'],
        ['Alicatado y solado', 'indigo'],
        ['Pladur y falsos techos', 'purple'],
        ['Pintura', 'pink'],
        ['Cocina y baño', 'green'],
        ['Cristalería', 'teal'],
        ['Cerrajería', 'gray'],
        ['Limpieza final', 'blue'],
    ];

    public function run(): void
    {
        foreach (self::TRADES as $position => [$name, $color]) {
            Trade::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'color' => $color, 'position' => $position],
            );
        }
    }
}