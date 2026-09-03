<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
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
