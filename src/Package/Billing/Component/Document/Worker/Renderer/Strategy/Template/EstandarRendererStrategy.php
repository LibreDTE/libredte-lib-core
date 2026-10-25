<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Renderer\Strategy\Template;

use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererStrategyInterface;

/**
 * Renderizador de DTE usando la plantilla estándar de LibreDTE.
 */
#[Strategy(name: 'template.estandar', worker: 'renderer', component: 'document', package: 'billing')]
class EstandarRendererStrategy extends AbstractRendererStrategy implements RendererStrategyInterface
{
    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        '__allowUndefinedKeys' => true,
        'template' => [
            'types' => 'string',
            'default' => 'estandar',
        ],
        'format' => [
            'types' => 'string',
            'default' => 'pdf',
        ],
        // Configuración pasada tal cual al motor de PDF (mPDF). Se define
        // acá, vía PHP, y no con `@page` en el CSS de la plantilla: mPDF no
        // soporta `@page` anidado dentro de `@media` (como usa el resto de
        // los estilos "solo para impresión" de la plantilla) y, al toparse
        // con esa regla, corrompe el parseo del resto de la hoja de estilos
        // (por ejemplo, ignora silenciosamente el `background-color` del
        // body) y dejaba un espacio en blanco enorme antes del encabezado.
        'pdf' => [
            'types' => 'array',
            'schema' => [
                // Carta (Letter, 215.9 x 279.4mm / 612 x 792pt): mismo
                // tamaño que emite el SII (verificado con `pdfinfo` sobre un
                // PDF real de referencia). Antes se usaba 216x260mm (una
                // hoja recortada ~19mm de alto respecto a Carta real).
                'format' => [
                    'types' => 'string',
                    'default' => 'Letter',
                ],
                // Ajustado a ojo (14mm) tras comparar con el PDF de
                // referencia del SII: 18mm (la medición inicial) dejaba el
                // emisor un poco más abajo de lo esperado.
                'margin_top' => [
                    'types' => 'int',
                    'default' => 14,
                ],
                'margin_bottom' => [
                    'types' => 'int',
                    'default' => 5,
                ],
                'margin_left' => [
                    'types' => 'int',
                    'default' => 5,
                ],
                'margin_right' => [
                    'types' => 'int',
                    'default' => 5,
                ],
                'margin_header' => [
                    'types' => 'int',
                    'default' => 0,
                ],
                'margin_footer' => [
                    'types' => 'int',
                    'default' => 0,
                ],
            ],
        ],
    ];
}
