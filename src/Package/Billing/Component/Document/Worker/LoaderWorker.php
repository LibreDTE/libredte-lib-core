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
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;

/**
 * Clase para cargar documentos tributarios desde su XML.
 */
#[Worker(name: 'loader', component: 'document', package: 'billing')]
class LoaderWorker extends AbstractWorker implements LoaderWorkerInterface
{
    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'load_xml',
        summary: 'Carga un documento tributario desde su XML',
        description: <<<'MD'
            Carga un documento tributario electrónico completo (con sus datos normalizados, su timbre y su firma) desde su XML y entrega sus datos. Sirve, por ejemplo, para recibir un documento ya emitido y trabajar con sus datos. No valida el esquema ni la firma; para eso se usan `billing.document.validator::validate_schema` y `billing.document.validator::validate_signature`.

            ## Cómo se usa

            Se envía en `xml` el XML del documento codificado en base64, tal como lo entrega `billing.document.builder::build` en `document_xml`. Si el XML está vacío o no se puede leer, la operación falla.

            Para un sobre de documentos (`EnvioDTE`), que contiene varios, se usa `billing.document.dispatcher::load_xml`.
            MD,
        parameters: [
            'xml' => [
                'description' => 'XML del documento, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Documento cargado. `document` es el DTE con sus datos y `document_normalized` el XML leído como árbol, con los valores como texto. `document_xml` es el XML en base64, `document_stamp` el timbre (TED) en base64 y `document_id` identifica el documento (RUT del emisor, tipo y folio). `document_type` describe el tipo de documento. `emisor` y `receptor` se obtienen del XML. `caf`, `certificate` y `timbre` vienen en `null` porque el XML no incluye esos datos como objetos.
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
                                'RUTEmisor' => '76192083-9',
                                'RznSoc' => 'SASCO SpA',
                                'GiroEmis' => 'Servicios',
                                'Acteco' => 620100,
                                'DirOrigen' => 'Dirección 123',
                                'CmnaOrigen' => 'Santiago',
                            ],
                            'Receptor' => [
                                'RUTRecep' => '66666666-6',
                                'RznSocRecep' => 'Cliente de Prueba',
                                'GiroRecep' => 'Servicios',
                                'DirRecep' => 'Santiago',
                                'CmnaRecep' => 'Santiago',
                            ],
                            'Totales' => [
                                'MntNeto' => 10000,
                                'TasaIVA' => 19,
                                'IVA' => 1900,
                                'MntTotal' => 11900,
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NroLinDet' => 1,
                                'NmbItem' => 'Servicio de prueba',
                                'QtyItem' => 1,
                                'PrcItem' => 10000,
                                'MontoItem' => 10000,
                            ],
                        ],
                    ],
                    'document_parsed' => null,
                    'document_normalized' => [
                        '@attributes' => [
                            'ID' => 'LibreDTE_76192083-9_T033F000000000001',
                        ],
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => '33',
                                'Folio' => '1',
                                'FchEmis' => '2024-01-15',
                                'TpoTranVenta' => '1',
                            ],
                        ],
                        'TmstFirma' => '2024-01-15T10:00:00',
                    ],
                    'document_extra' => null,
                    'document_stamp' => 'PFRFRCB4bWxucz0iaHR0cDovL3d3dy5zaWkuY2wvU2lpRHRlIi...',
                    'document_auth' => null,
                    'document_type' => [
                        'codigo' => 33,
                        'nombre' => 'Factura electrónica',
                        'nombre_corto' => 'Factura',
                    ],
                    'document_id' => '76192083-9_T033F000000001',
                    'document_xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                    'options' => [
                        'builder' => [],
                        'normalizer' => [],
                        'parser' => [],
                        'renderer' => [],
                        'sanitizer' => [],
                        'validator' => [],
                    ],
                    'certificate' => null,
                    'emisor' => [
                        'rut' => '76192083-9',
                        'razon_social' => 'SASCO SpA',
                        'giro' => 'Servicios',
                        'actividades_economicas' => [620100],
                        'telefonos' => [],
                        'email' => null,
                        'direccion' => 'Dirección 123',
                        'comuna' => 'Santiago',
                        'ciudad' => null,
                        'sucursal' => null,
                        'codigo_sucursal' => null,
                        'vendedor' => null,
                        'autorizacion_dte' => null,
                        'logo' => null,
                    ],
                    'receptor' => [
                        'rut' => '66666666-6',
                        'razon_social' => 'Cliente de Prueba',
                        'giro' => 'Servicios',
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
                    'caf' => null,
                    'timbre' => null,
                ],
            ],
        ],
    )]
    public function loadXml(XmlDocumentInterface|string $xml): DocumentBagInterface
    {
        if (is_string($xml)) {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($xml);
        } else {
            $xmlDocument = $xml;
        }

        $bag = new DocumentBag();
        $bag->setXmlDocument($xmlDocument);

        return $this->documentBagManager->normalize($bag, all: true);
    }
}
