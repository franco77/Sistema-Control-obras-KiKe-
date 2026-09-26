<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Trade;
use Illuminate\Database\Seeder;

/**
 * Banco de precios de arranque. Los importes son orientativos: sirven para
 * que el constructor de presupuestos sea usable desde el primer día.
 */
class CatalogSeeder extends Seeder
{
    /** capítulo => [oficio, [código, nombre, unidad, coste, pvp, rendimiento]] */
    private const CATALOG = [
        'Demoliciones y trabajos previos' => ['Demolición', [
            ['DEM-001', 'Demolición de tabique de ladrillo hueco, incluso retirada de escombros', 'm2', 9.50, 18.00, 12],
            ['DEM-002', 'Picado de alicatado en paramentos verticales', 'm2', 8.00, 15.50, 15],
            ['DEM-003', 'Levantado de solado existente', 'm2', 7.50, 14.00, 18],
            ['DEM-004', 'Desmontaje de sanitarios y grifería', 'ud', 22.00, 45.00, 6],
            ['DEM-005', 'Contenedor de escombros 6 m³, incluso portes y canon', 'ud', 180.00, 265.00, null],
        ]],

        'Albañilería' => ['Albañilería', [
            ['ALB-001', 'Tabique de ladrillo hueco doble 9 cm, recibido con mortero', 'm2', 21.00, 38.00, 10],
            ['ALB-002', 'Enfoscado maestreado de mortero en paramentos', 'm2', 12.00, 22.00, 14],
            ['ALB-003', 'Recibido de cerco de puerta', 'ud', 28.00, 52.00, 5],
            ['ALB-004', 'Formación de pendientes en ducha con mortero', 'ud', 75.00, 140.00, 2],
        ]],

        'Fontanería' => ['Fontanería', [
            ['FON-001', 'Instalación de punto de agua fría y caliente en multicapa', 'ud', 45.00, 85.00, 6],
            ['FON-002', 'Sustitución de bajante de PVC Ø110', 'ml', 32.00, 58.00, 8],
            ['FON-003', 'Montaje de plato de ducha con desagüe', 'ud', 95.00, 180.00, 2],
            ['FON-004', 'Instalación de inodoro suspendido con cisterna empotrada', 'ud', 160.00, 310.00, 1.5],
        ]],

        'Electricidad' => ['Electricidad', [
            ['ELE-001', 'Punto de luz sencillo con conductor libre de halógenos', 'ud', 26.00, 48.00, 10],
            ['ELE-002', 'Toma de corriente 16 A con toma de tierra', 'ud', 24.00, 44.00, 12],
            ['ELE-003', 'Cuadro general de mando y protección para vivienda', 'ud', 320.00, 590.00, 1],
            ['ELE-004', 'Boletín eléctrico e inscripción en industria', 'ud', 110.00, 190.00, null],
        ]],

        'Revestimientos' => ['Alicatado y solado', [
            ['REV-001', 'Alicatado con azulejo cerámico, colocado con cemento cola', 'm2', 19.00, 36.00, 12],
            ['REV-002', 'Solado de gres porcelánico rectificado', 'm2', 23.00, 42.00, 14],
            ['REV-003', 'Rodapié de gres o lacado, incluso remates', 'ml', 6.50, 12.50, 30],
        ]],

        'Pladur y falsos techos' => ['Pladur y falsos techos', [
            ['PLA-001', 'Falso techo continuo de placa de yeso laminado', 'm2', 17.00, 31.00, 16],
            ['PLA-002', 'Trasdosado autoportante con aislamiento de lana mineral', 'm2', 24.00, 43.00, 12],
            ['PLA-003', 'Formación de foseado para iluminación indirecta', 'ml', 21.00, 39.00, 8],
        ]],

        'Pintura' => ['Pintura', [
            ['PIN-001', 'Pintura plástica lisa, dos manos, sobre paramentos', 'm2', 4.20, 8.50, 45],
            ['PIN-002', 'Esmalte al agua sobre carpintería', 'm2', 8.00, 15.00, 20],
            ['PIN-003', 'Plastecido y lijado general de paramentos', 'm2', 3.50, 6.80, 40],
        ]],

        'Carpintería' => ['Carpintería de madera', [
            ['CAR-001', 'Puerta de paso lacada blanca, incluso herrajes y colocación', 'ud', 185.00, 340.00, 3],
            ['CAR-002', 'Frente de armario empotrado con puertas correderas', 'ml', 260.00, 470.00, 1.5],
        ]],

        'Cocina y baño' => ['Cocina y baño', [
            ['COC-001', 'Montaje de mobiliario de cocina', 'ml', 85.00, 155.00, 3],
            ['COC-002', 'Encimera de cuarzo compacto, incluso corte de fregadero', 'ml', 195.00, 340.00, 2],
        ]],

        'Varios' => ['Limpieza final', [
            ['VAR-001', 'Limpieza final de obra', 'm2', 2.20, 4.50, 60],
            ['VAR-002', 'Protección de suelos y mobiliario durante la obra', 'm2', 1.80, 3.50, 80],
            ['VAR-003', 'Dirección y coordinación de obra', 'pa', 0.00, 0.00, null],
        ]],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::CATALOG as $categoryName => [$tradeName, $items]) {
            $trade = Trade::where('name', $tradeName)->first();

            $category = CatalogCategory::firstOrCreate(
                ['name' => $categoryName],
                ['trade_id' => $trade?->id, 'position' => $position++],
            );

            foreach ($items as [$code, $name, $unit, $cost, $price, $yield]) {
                CatalogItem::firstOrCreate(
                    ['code' => $code],
                    [
                        'catalog_category_id' => $category->id,
                        'trade_id' => $trade?->id,
                        'name' => $name,
                        'unit' => $unit,
                        'unit_cost' => $cost,
                        'unit_price' => $price,
                        'default_quantity' => 1,
                        'yield_per_day' => $yield,
                    ],
                );
            }
        }
    }
}