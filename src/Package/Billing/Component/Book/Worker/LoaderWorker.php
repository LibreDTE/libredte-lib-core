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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use Throwable;

/**
 * Worker "billing.book.loader".
 *
 * Normaliza los datos de entrada de cualquier tipo de libro tributario desde
 * cualquier formato de origen (array, CSV, XML, etc.).
 *
 * Construye el nombre de estrategia como `{tipo}.{formato}` donde:
 *   - `tipo` proviene de `BookBagInterface::getTipo()`.
 *   - `formato` proviene de la opción `format` del bag (por defecto 'array').
 *
 * Ejemplos de estrategias disponibles:
 *   - `libro_ventas.array`, `libro_ventas.csv`, `libro_ventas.xml`
 *   - `libro_compras.array`, `libro_compras.csv`
 *   - `libro_boletas.array`
 *   - `libro_guias.array`
 *   - `resumen_ventas_diarias.array`
 */
#[Worker(name: 'loader', component: 'book', package: 'billing')]
class LoaderWorker extends AbstractWorker implements LoaderWorkerInterface
{
    use StrategiesAwareTrait;

    /**
     * Esquema de las opciones del worker.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $optionsSchema = [
        'format' => [
            'types' => 'string',
            'default' => 'array',
        ],
    ];

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(iterable $strategies = [])
    {
        $this->setStrategies($strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'load',
        summary: 'Normaliza los datos de un libro tributario',
        description: <<<'MD'
            Prepara los datos de un libro tributario electrónico para poder construirlo: completa la carátula con valores por defecto, normaliza cada fila del detalle y devuelve la misma estructura con los datos ya normalizados. No genera el XML del libro; para eso se usa `billing.book.builder::build`.

            ## Tipos de libro

            El campo `tipo` indica qué libro se prepara:

            | Valor | Libro |
            |---|---|
            | `libro_ventas` | Libro de ventas |
            | `libro_compras` | Libro de compras |
            | `libro_boletas` | Libro de boletas |
            | `libro_guias` | Libro de guías de despacho |
            | `resumen_ventas_diarias` | Resumen de ventas diarias |

            ## Cómo se usa

            Se envía `bag` con el tipo de libro, la carátula y las filas del detalle. Lo que no se indique en la carátula se completa con valores por defecto, que se describen en el parámetro `bag`. La respuesta trae el libro normalizado y puede enviarse a `billing.book.builder::build` para generar el XML.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del libro.

                    | Campo | Descripción |
                    |---|---|
                    | `tipo` | Obligatorio. Tipo de libro: `libro_ventas`, `libro_compras`, `libro_boletas`, `libro_guias` o `resumen_ventas_diarias`. |
                    | `caratula` | Datos de la carátula del libro. Lo que no se indique se completa con valores por defecto (ver más abajo). |
                    | `detalle` | Lista de filas del libro. Cada fila usa los nombres de campo del formato del SII, por ejemplo `TpoDoc` (tipo de documento), `NroDoc`, `FchDoc` (`AAAA-MM-DD`), `RUTDoc`, `RznSoc`, `MntNeto`, `MntIVA` y `MntTotal`. Los montos son números enteros. |
                    | `inputData` | Alternativa a `detalle`. Con el formato por defecto (`array`) es la lista de filas. Con otro formato en `options.loader.format` (por ejemplo `csv`, si la instalación lo incluye) es el contenido original codificado en base64. |
                    | `options` | Opciones. `options.loader.format` es el formato de `inputData`; por defecto `array`. |
                    | `emisor` | Datos del emisor. Su RUT se usa como `RutEmisorLibro`, y su autorización (`autorizacion_dte` con `fecha_resolucion` y `numero_resolucion`) como `FchResol` y `NroResol`, si no se indican en la carátula. |
                    | `certificate` | Certificado digital. Su RUT se usa como `RutEnvia`, si no se indica en la carátula. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. |

                    ### Carátula por defecto

                    Libro de ventas y libro de compras:

                    | Campo | Valor por defecto |
                    |---|---|
                    | `RutEmisorLibro` | RUT del `emisor` |
                    | `RutEnvia` | RUT del `certificate` |
                    | `PeriodoTributario` | Mes actual, formato `AAAA-MM` |
                    | `FchResol`, `NroResol` | Autorización del `emisor` |
                    | `TipoOperacion` | `VENTA` en el libro de ventas; `COMPRA` en el de compras |
                    | `TipoLibro` | `MENSUAL`. Otros valores: `ESPECIAL` (exige `FolioNotificacion`) y `RECTIFICA` (exige `CodAutRec`) |
                    | `TipoEnvio` | `TOTAL`. Otros valores: `PARCIAL`, `FINAL` y `AJUSTE` |

                    Libro de boletas y libro de guías de despacho: `TipoLibro` es siempre `ESPECIAL`, `TipoEnvio` es `TOTAL` por defecto (también `PARCIAL`, `FINAL` y `AJUSTE`) y `FolioNotificacion` es `1` por defecto. En el libro de boletas es un correlativo que se incrementa en `1` por cada nuevo envío del libro al SII.

                    Resumen de ventas diarias: `RutEmisor`, `RutEnvia`, `FchResol` y `NroResol` se completan como arriba; `FchInicio` y `FchFinal` no tienen valor por defecto, y `Correlativo` y `SecEnvio` parten en `1`.

                    ### Códigos de tipo de documento

                    Los códigos válidos para `TpoDoc` están en el catálogo: operación `system.repository.catalog::find_all` con `repository` igual a `libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface`.
                    MD,
                'example' => [
                    'tipo' => 'libro_ventas',
                    'caratula' => [
                        'RutEmisorLibro' => '76192083-9',
                        'PeriodoTributario' => '2024-01',
                    ],
                    'detalle' => [
                        [
                            'TpoDoc' => 33,
                            'NroDoc' => 1,
                            'TasaImp' => 19,
                            'FchDoc' => '2024-01-10',
                            'RUTDoc' => '12345678-9',
                            'RznSoc' => 'Empresa Compradora SpA',
                            'MntNeto' => 100000,
                            'MntIVA' => 19000,
                            'MntTotal' => 119000,
                        ],
                    ],
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'emisor' => [
                        'rut' => '76192083-9',
                        'razon_social' => 'SASCO SpA',
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Libro normalizado. `book` es `null` porque el XML aún no se construye. `caratula` trae todos los campos con sus valores por defecto, y cada fila de `detalle` trae todos los campos del formato (los que no tienen valor van como `null`; en el ejemplo se omiten por brevedad).
                    MD,
                'example' => [
                    'book' => null,
                    'book_auth' => null,
                    'book_type' => [
                        'codigo' => 'libro_ventas',
                        'nombre' => 'Libro de ventas',
                        'nombre_corto' => 'Ventas',
                    ],
                    'caratula' => [
                        'RutEmisorLibro' => '76192083-9',
                        'RutEnvia' => '76192083-9',
                        'PeriodoTributario' => '2024-01',
                        'FchResol' => null,
                        'NroResol' => null,
                        'TipoOperacion' => 'VENTA',
                        'TipoLibro' => 'MENSUAL',
                        'TipoEnvio' => 'TOTAL',
                        'FolioNotificacion' => null,
                    ],
                    'detalle' => [
                        [
                            'TpoDoc' => 33,
                            'NroDoc' => 1,
                            'TpoImp' => 1,
                            'TasaImp' => 19,
                            'FchDoc' => '2024-01-10',
                            'RUTDoc' => '12345678-9',
                            'RznSoc' => 'Empresa Compradora SpA',
                            'MntNeto' => 100000,
                            'MntIVA' => 19000,
                            'MntTotal' => 119000,
                        ],
                    ],
                    'resumen' => [],
                    'options' => [
                        'loader' => [],
                        'builder' => [],
                    ],
                ],
            ],
        ],
    )]
    public function load(BookBagInterface $bag): BookBagInterface
    {
        $options = $this->resolveOptions($bag->getLoaderOptions());
        $format = $options->getReader()->requireString('format');
        $strategyName = $bag->getTipo()->value . '.' . $format;
        $strategy = $this->getStrategy($strategyName);

        $strategy = ValueCaster::requireObject($strategy, LoaderStrategyInterface::class);

        try {
            return $strategy->load($bag);
        } catch (Throwable $e) {
            throw new BookException(
                message: [
                    'Could not load the book "{type}" from format "{format}": {detail}',
                    'type' => $bag->getTipo()->value,
                    'format' => $format,
                    'detail' => $e,
                ],
                previous: $e
            );
        }
    }
}
