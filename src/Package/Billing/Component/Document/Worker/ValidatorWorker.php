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
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ValidatorException;

/**
 * Clase para los validadores de documentos.
 */
#[Worker(name: 'validator', component: 'document', package: 'billing')]
class ValidatorWorker extends AbstractWorker implements ValidatorWorkerInterface
{
    use StrategiesAwareTrait;

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param DocumentBagManagerWorkerInterface $documentBagManager
     * @param XmlServiceInterface $xmlService
     * @param SignatureServiceInterface $signatureService
     * @param iterable<array-key, JobInterface> $jobs
     * @param iterable<array-key, HandlerInterface> $handlers
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        private XmlServiceInterface $xmlService,
        private SignatureServiceInterface $signatureService,
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
    #[Operation(
        name: 'validate',
        summary: 'Valida los datos de un documento',
        description: <<<'MD'
            Valida los datos de un documento tributario electrónico según las reglas de su tipo; por ejemplo, que los RUT tengan dígito verificador válido. No valida el esquema XSD ni la firma; para eso se usan `billing.document.validator::validate_schema` y `billing.document.validator::validate_signature`.

            ## Cómo se usa

            Se envía en `source` el XML del documento codificado en base64, por ejemplo el que entrega `billing.document.builder::build` en `document_xml`. Si los datos son válidos, la respuesta es el documento; si no, la operación falla con un error que indica el problema. Esta validación solo mira los datos del documento: no sirve para comprobar su firma.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del documento, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'Los datos son válidos. Se devuelve el documento como árbol del XML (sin firma).',
                'example' => [
                    'DTE' => [
                        'Documento' => [
                            'Encabezado' => [
                                'IdDoc' => [
                                    'TipoDTE' => '33',
                                    'Folio' => '1',
                                    'FchEmis' => '2024-01-15',
                                    'TpoTranVenta' => '1',
                                ],
                                'Emisor' => [
                                    'RUTEmisor' => '76192083-9',
                                    'RznSoc' => 'SASCO SpA',
                                    'GiroEmis' => 'Servicios',
                                    'Acteco' => '620100',
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
                                    'MntNeto' => '10000',
                                    'TasaIVA' => '19',
                                    'IVA' => '1900',
                                    'MntTotal' => '11900',
                                ],
                            ],
                            'Detalle' => [
                                'NroLinDet' => '1',
                                'NmbItem' => 'Servicio de prueba',
                                'QtyItem' => '1',
                                'PrcItem' => '10000',
                                'MontoItem' => '10000',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validate(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): ?XmlDocumentInterface {
        // Un XmlDocumentInterface se trata igual que un string: se extrae su
        // XML y se procesa por la misma vía. Se usa saveXml() (con cabecera
        // y encoding declarado) y no getXml() (sin cabecera, pensado para
        // embeber el documento dentro de otro XML): sin la cabecera, un XML
        // con caracteres no ASCII (ISO-8859-1) se reinterpreta como UTF-8 al
        // volver a parsearlo más abajo y falla con XmlParseException.
        if ($source instanceof XmlDocumentInterface) {
            $source = $source->saveXml();
        }

        if (is_string($source)) {
            // Importante: Esto quitará la firma, no sirve para otras
            // validaciones (de esquema o firma). Solo para esta validación que
            // solo valida los datos contenidos en el documento.
            $source = 'parser.strategy.default.xml:' . $source;
        }

        // Asignar la bolsa del DTE a partir de la fuente.
        $bag = $source instanceof DocumentBagInterface
            ? $source
            : $this->documentBagManager->create($source)
        ;

        // Si no hay tipo de documento no se podrá normalizar.
        if (!$bag->getTipoDocumento()) {
            throw new ValidatorException(
                'Cannot validate without a TipoDocumento in the $bag.'
            );
        }

        // Buscar la estrategia para validar el tipo de documento tributario.
        $strategy = $this->getStrategy($bag->requireTipoDocumento()->requireAlias());
        assert($strategy instanceof ValidatorStrategyInterface);

        // Validar el documento usando la estrategia.
        $strategy->validate($bag);

        // Devolver el documento XML validado. Si se valida una bolsa que aún
        // no tiene XML (se está construyendo el documento) no hay XML que
        // devolver.
        return $bag->getXmlDocument();
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_schema',
        summary: 'Valida el XML de un documento contra su esquema',
        description: <<<'MD'
            Valida el XML de un documento tributario electrónico contra el esquema XSD del SII.

            ## Cómo se usa

            Se envía en `source` el XML del documento codificado en base64. Si cumple el esquema, la respuesta es el mismo documento; si no, la operación falla con un error que indica el esquema usado y la línea y la columna del problema.

            Las boletas (tipos `39` y `41`) se validan contra un esquema propio de LibreDTE, derivado del de su sobre `EnvioBOLETA`, porque el SII no publica un esquema para la boleta suelta. El esquema del sobre se valida con `billing.document.dispatcher::validate_schema`.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del documento, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'El XML cumple el esquema. Se devuelve el documento como árbol del XML.',
                'example' => [
                    'DTE' => [
                        'Documento' => [
                            'Encabezado' => [
                                'IdDoc' => [
                                    'TipoDTE' => '33',
                                    'Folio' => '1',
                                    'FchEmis' => '2024-01-15',
                                    'TpoTranVenta' => '1',
                                ],
                                'Emisor' => [
                                    'RUTEmisor' => '76192083-9',
                                    'RznSoc' => 'SASCO SpA',
                                    'GiroEmis' => 'Servicios',
                                    'Acteco' => '620100',
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
                                    'MntNeto' => '10000',
                                    'TasaIVA' => '19',
                                    'IVA' => '1900',
                                    'MntTotal' => '11900',
                                ],
                            ],
                            'Detalle' => [
                                'NroLinDet' => '1',
                                'NmbItem' => 'Servicio de prueba',
                                'QtyItem' => '1',
                                'PrcItem' => '10000',
                                'MontoItem' => '10000',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validateSchema(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        // Obtener el documento XML.
        if ($source instanceof DocumentBagInterface) {
            $xmlDocument = $source->requireXmlDocument();
        } elseif ($source instanceof XmlDocumentInterface) {
            $xmlDocument = $source;
        } else {
            // Importante: Hacerlo de esta forma garantiza que no se pierda el
            // nodo Signature. Pues se carga el XML completo a XmlDocument.
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($source);
        }

        // Crear una bolsa con el documento XML. Con esto se obtendrá en la
        // bolsa el tipo de documento asociado al DTE.
        $bag = $this->documentBagManager->create($xmlDocument, normalizeAll: false);

        // El SII no tiene un esquema para el DTE de una boleta suelta: la define
        // solo dentro del EnvioBOLETA. Por eso las boletas se validan con un
        // esquema auxiliar que reutiliza esa definición.
        $schemaFile = $bag->requireTipoDocumento()->isBoleta()
            ? 'BOLETA_v11.xsd'
            : 'DTE_v10.xsd'
        ;
        $schema = dirname(__DIR__, 6) . '/resources/schemas/' . $schemaFile;
        $this->xmlService->validate($bag->requireXmlDocument(), $schema);

        return $bag->requireXmlDocument();
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_signature',
        summary: 'Valida la firma electrónica de un documento',
        description: <<<'MD'
            Valida la firma electrónica (XML DSIG) de un documento tributario electrónico y entrega los datos de la firma y del certificado del firmante.

            ## Cómo se usa

            Se envía en `source` el XML del documento firmado, codificado en base64.

            La operación responde con éxito tanto si la firma es válida como si no lo es: el resultado está en el campo `valid`. Si es `false`, el campo `error` explica el motivo (por ejemplo, que el contenido se modificó después de firmarlo).
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del documento firmado, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => '`valid` indica si la firma es válida y `error` trae el motivo cuando no lo es (`null` si es válida). `signature` trae los datos de la firma y `signature.signer` los del certificado del firmante.',
                'example' => [
                    'valid' => true,
                    'error' => null,
                    'signature' => [
                        'reference' => 'LibreDTE_76192083-9_T033F000000000001',
                        'canonicalization_method' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                        'signature_method' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                        'digest_method' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                        'digest_value' => 'NfiddEW6DjPTPBefnLCHkULI1fg=',
                        'signature_value' => 'QdjpaqidVjAJXEjcVwY0CQvmXWAYmRODuL5T...',
                        'modulus' => 'hUNOQsNO9kb6QPfMeDTzDGruGejPM2O0...',
                        'exponent' => 'AQAB',
                        'certificate' => 'MIID8zCCAtugAwIBAgIBADANBgkqhkiG9w0B...',
                        'certificate_chain' => [],
                        'signer' => [
                            'id' => '12345678-9',
                            'name' => 'Juan Pérez',
                            'email' => 'juan.perez@example.com',
                            'from' => '2024-01-01T00:00:00',
                            'to' => '2025-01-01T00:00:00',
                            'total_days' => 366,
                            'expiration_days' => 300,
                            'is_active' => true,
                            'issuer' => 'AC de Ejemplo',
                            'modulus' => 'hUNOQsNO9kb6QPfMeDTzDGruGejPM2O0...',
                            'exponent' => 'AQAB',
                            'cert' => "-----BEGIN CERTIFICATE-----\nMIID8zCCAtugAwIBAgIB...",
                            'chain' => [],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validateSignature(
        DocumentBagInterface|XmlDocumentInterface|string $source
    ): SignatureValidationResultInterface {
        $xml = $source instanceof DocumentBagInterface
            ? $source->requireXmlDocument()
            : $source
        ;

        return $this->signatureService->validateXml($xml)[0];
    }
}
