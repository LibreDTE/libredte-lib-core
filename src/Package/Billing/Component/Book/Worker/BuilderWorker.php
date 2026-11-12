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
use Derafu\Signature\Contract\SignatureServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use Throwable;

/**
 * Worker "billing.book.builder".
 *
 * Construye el XML y la entidad resultante de cualquier tipo de libro
 * tributario a partir del bag con detalles normalizados por el `LoaderWorker`.
 *
 * Selecciona la estrategia usando `BookBagInterface::getTipo()` directamente:
 *   - `libro_ventas`
 *   - `libro_compras`
 *   - `libro_boletas`
 *   - `libro_guias`
 *   - `resumen_ventas_diarias`
 */
#[Worker(name: 'builder', component: 'book', package: 'billing')]
class BuilderWorker extends AbstractWorker implements BuilderWorkerInterface
{
    use StrategiesAwareTrait;

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param SignatureServiceInterface $signatureService
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(
        private SignatureServiceInterface $signatureService,
        iterable $strategies = [],
    ) {
        $this->setStrategies($strategies);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'build',
        summary: 'Construye un libro tributario',
        description: <<<'MD'
            Construye un libro tributario electrónico: calcula los totales del período, genera el XML según el esquema del SII y, si se entrega un certificado, lo firma (los libros en formato simplificado no se firman).

            ## Tipos de libro

            El campo `tipo` indica qué libro se construye:

            | Valor | Libro |
            |---|---|
            | `libro_ventas` | Libro de ventas |
            | `libro_compras` | Libro de compras |
            | `libro_boletas` | Libro de boletas |
            | `libro_guias` | Libro de guías de despacho |
            | `resumen_ventas_diarias` | Resumen de ventas diarias |

            ## Cómo se usa

            Se envía `bag` con el tipo de libro, la carátula y las filas del detalle. Lo habitual es normalizar antes con `billing.book.loader::load`, que completa `RutEmisorLibro`, `RutEnvia`, `FchResol` y `NroResol` desde `emisor` y `certificate`, y enviar a esta operación la respuesta de esa. Si se envían datos sin normalizar, esta operación solo completa `PeriodoTributario` (mes actual), `TipoOperacion`, `TipoLibro` (`MENSUAL`) y `TipoEnvio` (`TOTAL`): el resto de la carátula debe indicarse.

            El libro construido está en `book`: su contenido como árbol y el XML completo codificado en base64 (`book.xml`). Ese XML puede validarse con `billing.book.validator::validate_schema` y `billing.book.validator::validate_signature`.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del libro, con la misma estructura que en `billing.book.loader::load`, donde se describen todos los campos, los valores por defecto de la carátula y los códigos de tipo de documento.

                    Para generar un libro firmado se debe entregar `certificate` (con `certificate` y `privateKey` en PEM, `data` en base64 y `password`, o `cert` y `pkey`).
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
                    Libro construido. `book` trae el libro completo, con los valores por defecto aplicados y los totales del período calculados: `book.xml` es el XML en base64 y el resto es su contenido como árbol. `caratula` y `detalle` repiten los datos recibidos. `book_type` identifica el tipo de libro.
                    MD,
                'example' => [
                    'book' => [
                        'LibroCompraVenta' => [
                            'EnvioLibro' => [
                                'Caratula' => [
                                    'RutEmisorLibro' => '76192083-9',
                                    'PeriodoTributario' => '2024-01',
                                    'TipoOperacion' => 'VENTA',
                                    'TipoLibro' => 'MENSUAL',
                                    'TipoEnvio' => 'TOTAL',
                                ],
                                'ResumenPeriodo' => [
                                    'TotalesPeriodo' => [
                                        'TpoDoc' => '33',
                                        'TotDoc' => '1',
                                        'TotMntExe' => '0',
                                        'TotMntNeto' => '100000',
                                        'TotMntIVA' => '19000',
                                        'TotMntTotal' => '119000',
                                    ],
                                ],
                                'Detalle' => [
                                    'TpoDoc' => '33',
                                    'NroDoc' => '1',
                                    'TasaImp' => '19',
                                    'FchDoc' => '2024-01-10',
                                    'RUTDoc' => '12345678-9',
                                    'RznSoc' => 'Empresa Compradora SpA',
                                    'MntNeto' => '100000',
                                    'MntIVA' => '19000',
                                    'MntTotal' => '119000',
                                ],
                                'TmstFirma' => '2024-02-01T10:00:00',
                            ],
                            'Signature' => [
                                'SignedInfo' => [
                                    'CanonicalizationMethod' => '',
                                    'SignatureMethod' => '',
                                    'Reference' => [
                                        'Transforms' => [
                                            'Transform' => '',
                                        ],
                                        'DigestMethod' => '',
                                        'DigestValue' => 'M7x5h42ilMuf3ewJGL+6/twRe4s=',
                                    ],
                                ],
                                'SignatureValue' => 'BJF3oJH09VqXeKB9...',
                                'KeyInfo' => [
                                    'KeyValue' => [
                                        'RSAKeyValue' => [
                                            'Modulus' => '3xxsX5l2lZV/wzz+...',
                                            'Exponent' => 'AQAB',
                                        ],
                                    ],
                                    'X509Data' => [
                                        'X509Certificate' => 'MIID8zCCAtugAwIBAgIB...',
                                    ],
                                ],
                            ],
                        ],
                        'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pg...',
                    ],
                    'book_auth' => null,
                    'book_type' => [
                        'codigo' => 'libro_ventas',
                        'nombre' => 'Libro de ventas',
                        'nombre_corto' => 'Ventas',
                    ],
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
                    'resumen' => [],
                    'options' => [
                        'loader' => [],
                        'builder' => [],
                    ],
                ],
            ],
        ],
    )]
    public function build(BookBagInterface $bag): BookBagInterface
    {
        $strategyName = $bag->getTipo()->value;
        $strategy = $this->getStrategy($strategyName);

        assert($strategy instanceof BuilderStrategyInterface);

        try {
            if ($bag->getResumen() !== [] && !$bag->getTipo()->acceptsResumen()) {
                throw new BookException([
                    'The book "{type}" does not have a manual summary.',
                    'type' => $strategyName,
                ]);
            }

            $libro = $strategy->build($bag);

            // Firmar el XML si se proporcionó un certificado.
            $certificate = $bag->getCertificate();
            if ($certificate !== null && !$libro->isSimplificado()) {
                $signedXml = $this->signatureService->signXml(
                    $libro->saveXml(),
                    $certificate,
                    [
                        'reference' => $libro->getId(),
                        'signature_namespace' => $libro->getSignatureNamespace(),
                    ]
                );
                $libro->getXmlDocument()->loadXml($signedXml);
            }

            // Guardar el libro en el bag para uso posterior (ej: validación).
            $bag->setBook($libro);

            return $bag;
        } catch (Throwable $e) {
            throw new BookException(
                message: [
                    'Could not build the book "{type}": {detail}',
                    'type' => $bag->getTipo()->value,
                    'detail' => $e,
                ],
                previous: $e
            );
        }
    }
}
