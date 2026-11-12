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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;

/**
 * Clase para los constructores de documentos.
 */
#[Worker(name: 'builder', component: 'document', package: 'billing')]
class BuilderWorker extends AbstractWorker implements BuilderWorkerInterface
{
    use StrategiesAwareTrait;

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param DocumentBagManagerWorkerInterface $documentBagManager
     * @param iterable<array-key, JobInterface> $jobs
     * @param iterable<array-key, HandlerInterface> $handlers
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        iterable $jobs = [],
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        $this->setJobs($jobs);
        $this->setHandlers($handlers);
        $this->setStrategies($strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function create(DocumentBagInterface $bag): DocumentBagInterface
    {
        // Buscar la estrategia para crear el documento tributario.
        $strategy = $this->getStrategy(
            $bag->requireTipoDocumento()->requireAlias()
        );
        $strategy = ValueCaster::requireObject($strategy, BuilderStrategyInterface::class);

        // Construir el documento usando la estrategia.
        $document = $strategy->create($bag->requireXmlDocument());
        $bag->setDocument($document);

        // Entregar la bolsa con el DTE construído.
        return $bag;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'build',
        summary: 'Construye un documento tributario electrónico',
        description: <<<'MD'
            Construye un documento tributario electrónico (DTE) a partir de sus datos: normaliza los datos, calcula los totales y genera el XML. Según lo que se entregue, el resultado es:

            - **Borrador**: si solo se entregan los datos del documento.
            - **Documento timbrado**: si además se entrega el folio y el CAF.
            - **Documento timbrado y firmado**: si además se entrega el certificado digital.

            ## Tipos de documento

            El tipo se toma de `Encabezado.IdDoc.TipoDTE` de los datos. Se pueden construir:

            | `TipoDTE` | Documento |
            |---|---|
            | `33` | Factura electrónica |
            | `34` | Factura no afecta o exenta electrónica |
            | `39` | Boleta electrónica |
            | `41` | Boleta no afecta o exenta electrónica |
            | `43` | Liquidación factura electrónica |
            | `46` | Factura de compra electrónica |
            | `52` | Guía de despacho electrónica |
            | `56` | Nota de débito electrónica |
            | `61` | Nota de crédito electrónica |
            | `110` | Factura de exportación electrónica |
            | `111` | Nota de débito de exportación electrónica |
            | `112` | Nota de crédito de exportación electrónica |

            ## Cómo se usa

            Se envía `bag` con los datos del documento en `parsedData` (o `inputData`, que se interpreta con `billing.document.parser::parse`), el CAF en `caf` y el certificado en `certificate`. El DTE construido está en `document`, y su XML codificado en base64 en `document_xml`.

            Los datos siguen el formato de los documentos tributarios electrónicos del SII, descrito en [Formato de los Documentos Tributarios Electrónicos](https://www.sii.cl/factura_electronica/factura_mercado/formato_dte_202602.pdf). Para las boletas, el formato está en [Formato de las Boletas Electrónicas](https://www.sii.cl/factura_electronica/factura_mercado/boletas_elec_0720_3.pdf).
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del documento.

                    | Campo | Descripción |
                    |---|---|
                    | `parsedData` | Datos del documento con la estructura del SII (`Encabezado`, `Detalle`, `Referencia`, etc.). |
                    | `inputData` | Alternativa a `parsedData`: datos de entrada sin interpretar, que se leen con la estrategia indicada en `options.parser.strategy` (ver `billing.document.parser::parse`). |
                    | `caf` | CAF (Código de Autorización de Folios) que cubre el folio del documento, como XML codificado en base64. Sin él el documento queda como borrador. |
                    | `certificate` | Certificado digital con el que se firma el documento. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. Sin él el documento no se firma. |
                    | `options` | Opciones de las etapas del proceso, por ejemplo `options.parser.strategy`. |
                    MD,
                'example' => [
                    'parsedData' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                            ],
                            'Emisor' => [
                                'RUTEmisor' => '12345678-5',
                                'RznSoc' => 'Empresa S.A.',
                                'GiroEmis' => 'Giro de la empresa',
                                'DirOrigen' => 'Santiago',
                                'CmnaOrigen' => 'Santiago',
                            ],
                            'Receptor' => [
                                'RUTRecep' => '23456789-6',
                                'RznSocRecep' => 'Empresa S.A.',
                                'GiroRecep' => 'Giro de la empresa',
                                'DirRecep' => 'Santiago',
                                'CmnaRecep' => 'Santiago',
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NmbItem' => 'Producto A',
                                'QtyItem' => 1,
                                'PrcItem' => 1000,
                            ],
                        ],
                    ],
                    'caf' => '',
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Documento construido. `document` es el DTE con todos sus datos, `document_xml` su XML en base64 y `document_stamp` el timbre (TED) en base64; `timbre` es ese mismo timbre como árbol. `document_id` identifica el documento (RUT del emisor, tipo y folio). `document_type` describe el tipo de documento. `document_normalized` trae los datos normalizados con todos los campos del formato (los que no tienen valor van como `null`; en el ejemplo se omiten por brevedad). `emisor`, `receptor`, `caf` y `certificate` repiten los datos usados. Sin CAF no hay timbre, y sin certificado el XML no está firmado.
                    MD,
                'example' => [
                    'document' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                                'FchEmis' => '2024-01-15',
                                'TpoTranVenta' => 1,
                            ],
                            'Emisor' => [
                                'RUTEmisor' => '12345678-5',
                                'RznSoc' => 'Empresa S.A.',
                                'GiroEmis' => 'Giro de la empresa',
                                'DirOrigen' => 'Santiago',
                                'CmnaOrigen' => 'Santiago',
                            ],
                            'Receptor' => [
                                'RUTRecep' => '23456789-6',
                                'RznSocRecep' => 'Empresa S.A.',
                                'GiroRecep' => 'Giro de la empresa',
                                'DirRecep' => 'Santiago',
                                'CmnaRecep' => 'Santiago',
                            ],
                            'Totales' => [
                                'MntNeto' => 1000,
                                'TasaIVA' => 19,
                                'IVA' => 190,
                                'MntTotal' => 1190,
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NroLinDet' => 1,
                                'NmbItem' => 'Producto A',
                                'QtyItem' => 1,
                                'PrcItem' => 1000,
                                'MontoItem' => 1000,
                            ],
                        ],
                    ],
                    'document_parsed' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                            ],
                            'Emisor' => [
                                'RUTEmisor' => '12345678-5',
                                'RznSoc' => 'Empresa S.A.',
                                'GiroEmis' => 'Giro de la empresa',
                                'DirOrigen' => 'Santiago',
                                'CmnaOrigen' => 'Santiago',
                            ],
                            'Receptor' => [
                                'RUTRecep' => '23456789-6',
                                'RznSocRecep' => 'Empresa S.A.',
                                'GiroRecep' => 'Giro de la empresa',
                                'DirRecep' => 'Santiago',
                                'CmnaRecep' => 'Santiago',
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NmbItem' => 'Producto A',
                                'QtyItem' => 1,
                                'PrcItem' => 1000,
                            ],
                        ],
                    ],
                    'document_normalized' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                                'FchEmis' => '2024-01-15',
                                'TpoTranVenta' => 1,
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NroLinDet' => 1,
                                'NmbItem' => 'Producto A',
                                'QtyItem' => 1,
                                'PrcItem' => 1000,
                                'MontoItem' => 1000,
                            ],
                        ],
                    ],
                    'document_extra' => null,
                    'document_stamp' => 'PFRFRCB4bWxucz0iaHR0cDovL3d3dy5zaWkuY2wvU2lpRHRlIiB2ZXJzaW9u...',
                    'document_auth' => null,
                    'document_type' => [
                        'codigo' => 33,
                        'nombre' => 'Factura electrónica',
                        'nombre_corto' => 'Factura',
                        'categoria' => [
                            'codigo' => 'T',
                            'nombre' => 'Tributario oficial del SII.',
                        ],
                        'operacion' => [
                            'codigo' => 'S',
                            'nombre' => 'Suma',
                        ],
                        'es_electronico' => true,
                        'se_envia_al_sii' => true,
                        'disponible_en_compras' => true,
                        'disponible_en_ventas' => true,
                        'es_cedible' => true,
                        'es_exento' => false,
                        'es_boleta' => false,
                        'es_exportacion' => false,
                        'es_guia_despacho' => false,
                    ],
                    'document_id' => '12345678-5_T033F000000001',
                    'document_xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                    'options' => [
                        'builder' => [],
                        'normalizer' => [],
                        'parser' => [],
                        'renderer' => [],
                        'sanitizer' => [],
                        'validator' => [],
                    ],
                    'certificate' => [
                        'id' => '12345678-9',
                        'name' => 'Juan Pérez',
                        'email' => 'juan.perez@example.com',
                        'from' => '2024-01-01T00:00:00',
                        'to' => '2025-01-01T00:00:00',
                        'total_days' => 366,
                        'expiration_days' => 300,
                        'is_active' => true,
                        'issuer' => 'AC de Ejemplo',
                        'modulus' => 'rta/vl0+RO1n1oBpvS0WB6/UvN+F+OQS...',
                        'exponent' => 'AQAB',
                        'cert' => "-----BEGIN CERTIFICATE-----\nMIIEbjCCA1agAwIBAgIB...",
                        'chain' => [],
                        'pkey' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0B...",
                    ],
                    'emisor' => [
                        'rut' => '12345678-5',
                        'razon_social' => 'Empresa S.A.',
                        'giro' => 'Giro de la empresa',
                        'actividades_economicas' => [],
                        'telefonos' => [],
                        'email' => null,
                        'direccion' => 'Santiago',
                        'comuna' => 'Santiago',
                        'ciudad' => null,
                        'sucursal' => null,
                        'codigo_sucursal' => null,
                        'vendedor' => null,
                        'autorizacion_dte' => null,
                        'logo' => null,
                    ],
                    'receptor' => [
                        'rut' => '23456789-6',
                        'razon_social' => 'Empresa S.A.',
                        'giro' => 'Giro de la empresa',
                        'actividades_economicas' => [],
                        'telefonos' => [],
                        'email' => null,
                        'direccion' => 'Santiago',
                        'comuna' => 'Santiago',
                        'ciudad' => null,
                        'codigo_interno' => null,
                        'nacionalidad' => null,
                        'identificador_extranjero' => null,
                    ],
                    'caf' => [
                        'id' => 'CAF33D1H100',
                        'emisor' => [
                            'rut' => '12345678-5',
                            'razon_social' => 'Empresa S.A.',
                        ],
                        'tipo_documento' => 33,
                        'folio_desde' => 1,
                        'folio_hasta' => 100,
                        'cantidad_folios' => 100,
                        'fecha_autorizacion' => '2024-01-15',
                        'fecha_vencimiento' => '2024-07-14',
                        'meses_autorizacion' => 0,
                        'vigente' => true,
                        'vence' => true,
                        'idk' => 666,
                        'ambiente' => null,
                        'certificacion' => null,
                        'public_key' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOC...",
                        'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASC...",
                        'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pg...',
                    ],
                    'timbre' => [
                        'TED' => [
                            '@attributes' => [
                                'version' => '1.0',
                            ],
                            'DD' => [
                                'RE' => '12345678-5',
                                'TD' => 33,
                                'F' => 1,
                                'FE' => '2024-01-15',
                                'RR' => '23456789-6',
                                'RSR' => 'Empresa S.A.',
                                'MNT' => 1190,
                                'IT1' => 'Producto A',
                                'TSTED' => '2024-01-15T10:00:00',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function build(DocumentBagInterface $bag): DocumentBagInterface
    {
        // Normalizar la bolsa con los datos del documento.
        // Acá no se puede normalizar todo, solo lo necesario. El resto de la
        // normalización (todo) se debe hacer al final de este método antes de
        // retornar. Esto es así porque para hacer la normalización completa se
        // requiere que el DTE esté creado.
        $bag = $this->documentBagManager->normalize($bag);

        // Buscar la estrategia para construir el documento tributario.
        $strategy = $this->getStrategy($bag->getAlias());
        $strategy = ValueCaster::requireObject($strategy, BuilderStrategyInterface::class);

        // Construir el documento usando la estrategia.
        $strategy->build($bag);

        // Normalizar la bolsa con los datos actualizados del documento.
        $bag = $this->documentBagManager->normalize($bag, all: true);

        // Entregar la bolsa con el DTE construído.
        return $bag;
    }
}
