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

use DateTime;
use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DispatcherWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentEnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\SobreEnvio;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Dispatcher\Job\ValidateEnvelopeJob;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Mandatario;

/**
 * Clase para la gestión de sobres de documentos (para envíos/transferencias).
 */
#[Worker(name: 'dispatcher', component: 'document', package: 'billing')]
class DispatcherWorker extends AbstractWorker implements DispatcherWorkerInterface
{
    /**
     * Constructor del worker y sus dependencias.
     *
     * @param XmlServiceInterface $xmlService
     * @param SignatureServiceInterface $signatureService
     * @param DocumentBagManagerWorkerInterface $documentBagManagerWorker
     * @param ValidateEnvelopeJob $validateEnvelopeJob
     * @param iterable<array-key, JobInterface> $jobs
     * @param iterable<array-key, HandlerInterface> $handlers
     */
    public function __construct(
        private XmlServiceInterface $xmlService,
        private SignatureServiceInterface $signatureService,
        private DocumentBagManagerWorkerInterface $documentBagManagerWorker,
        private ValidateEnvelopeJob $validateEnvelopeJob,
        iterable $jobs = [],
        iterable $handlers = []
    ) {
        $this->setJobs($jobs);
        $this->setHandlers($handlers);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'create',
        summary: 'Crea el sobre de envío de un documento',
        description: <<<'MD'
            Crea el sobre de envío (`EnvioDTE` o `EnvioBOLETA`) que contiene un documento tributario electrónico ya construido, con su carátula, y lo firma con el certificado digital. El sobre es el archivo que se envía al SII.

            ## Cómo se usa

            Se envía `bag` con el XML del documento (que se obtiene de `billing.document.builder::build` en `document_xml`), el certificado con el que se firma el sobre y el emisor con su autorización del SII (`autorizacion_dte`). Para enviar varios documentos en un mismo sobre se usa `billing.document.dispatcher::create_many`.

            La carátula del sobre se arma solo si hay emisor con su autorización del SII, mandatario (que se obtiene del certificado) y receptor; si falta alguno, el XML queda sin carátula y el error aparece al validar el esquema con `billing.document.dispatcher::validate_schema`.

            En la respuesta, `xml` es el XML del sobre codificado en base64 y `documents` trae los documentos que contiene.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del documento y de su envío.

                    | Campo | Descripción |
                    |---|---|
                    | `xmlDocument` | Obligatorio. XML del documento ya construido, codificado en base64. |
                    | `certificate` | Certificado digital con el que se firma el sobre. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. Debe estar vigente al momento de firmar. |
                    | `emisor` | Emisor del documento. `autorizacion_dte` (`fecha_resolucion` y `numero_resolucion`) es la autorización del SII y va en la carátula. |
                    MD,
                'example' => [
                    'xmlDocument' => '',
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'emisor' => [
                        'rut' => '76192083-9',
                        'razon_social' => 'SASCO SpA',
                        'autorizacion_dte' => [
                            'fecha_resolucion' => '2014-08-22',
                            'numero_resolucion' => 80,
                        ],
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Sobre creado. `tag` es el tipo de sobre (`EnvioDTE`), `xml` el XML del sobre en base64 y `caratula` su carátula: `RutEmisor`, `RutEnvia` (RUT del certificado), `RutReceptor`, `FchResol` y `NroResol` (autorización del emisor), `TmstFirmaEnv` (fecha y hora de la firma) y `SubTotDTE` (cantidad de documentos por tipo). `documents` trae los documentos del sobre con la misma estructura que entrega `billing.document.builder::build`.
                    MD,
                'example' => [
                    'tag' => 'EnvioDTE',
                    'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                    'documents' => [
                        [
                            'document' => [
                                'Encabezado' => [
                                    'IdDoc' => [
                                        'TipoDTE' => 33,
                                        'Folio' => 1,
                                        'FchEmis' => '2024-01-15',
                                    ],
                                ],
                            ],
                            'document_id' => '76192083-9_T033F000000001',
                            'document_xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                        ],
                    ],
                    'caratula' => [
                        '@attributes' => [
                            'version' => '1.0',
                        ],
                        'RutEmisor' => '76192083-9',
                        'RutEnvia' => '12345678-9',
                        'RutReceptor' => '66666666-6',
                        'FchResol' => '2014-08-22',
                        'NroResol' => 80,
                        'TmstFirmaEnv' => '2024-01-15T10:00:00',
                        'SubTotDTE' => [
                            [
                                'TpoDTE' => 33,
                                'NroDTE' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function create(DocumentBagInterface $bag): DocumentEnvelopeInterface
    {
        $this->documentBagManagerWorker->normalize($bag, true);

        $envelope = new DocumentEnvelope();
        $envelope->addDocument($bag);
        $envelope->setCertificate($bag->getCertificate());

        $this->normalize($envelope);

        return $envelope;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'create_many',
        summary: 'Crea el sobre de envío de varios documentos',
        description: <<<'MD'
            Crea un solo sobre de envío (`EnvioDTE` o `EnvioBOLETA`) que contiene varios documentos tributarios electrónicos ya construidos, y lo firma con el certificado digital. Funciona como `billing.document.dispatcher::create`, pero con una lista de documentos.

            ## Cómo se usa

            Se envía `bags`, una lista con los datos de cada documento (en la forma que se describe en `billing.document.dispatcher::create`). Todos los documentos deben ser del mismo emisor: el certificado y la carátula del sobre se toman del primer elemento. Si la lista viene vacía, la operación falla, y también si el sobre supera la cantidad de tipos de documento permitida o el certificado no está vigente.
            MD,
        parameters: [
            'bags' => [
                'description' => 'Lista de documentos. Cada elemento tiene `xmlDocument` (XML del documento en base64), `certificate` y `emisor`, igual que `bag` en `billing.document.dispatcher::create`. El certificado y el emisor del primer elemento se usan para todo el sobre.',
                'example' => [
                    [
                        'xmlDocument' => '',
                        'certificate' => [
                            'certificate' => '',
                            'privateKey' => '',
                        ],
                        'emisor' => [
                            'rut' => '76192083-9',
                            'razon_social' => 'SASCO SpA',
                            'autorizacion_dte' => [
                                'fecha_resolucion' => '2014-08-22',
                                'numero_resolucion' => 80,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => 'Sobre creado, con la misma estructura que entrega `billing.document.dispatcher::create`: `tag`, `xml` (en base64), `documents` (todos los documentos del sobre) y `caratula`, donde `SubTotDTE` indica la cantidad de documentos de cada tipo.',
                'example' => [
                    'tag' => 'EnvioDTE',
                    'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                    'documents' => [
                        [
                            'document' => [
                                'Encabezado' => [
                                    'IdDoc' => [
                                        'TipoDTE' => 33,
                                        'Folio' => 1,
                                        'FchEmis' => '2024-01-15',
                                    ],
                                ],
                            ],
                            'document_id' => '76192083-9_T033F000000001',
                            'document_xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                        ],
                        [
                            'document' => [
                                'Encabezado' => [
                                    'IdDoc' => [
                                        'TipoDTE' => 33,
                                        'Folio' => 2,
                                        'FchEmis' => '2024-01-15',
                                    ],
                                ],
                            ],
                            'document_id' => '76192083-9_T033F000000002',
                            'document_xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                        ],
                    ],
                    'caratula' => [
                        '@attributes' => [
                            'version' => '1.0',
                        ],
                        'RutEmisor' => '76192083-9',
                        'RutEnvia' => '12345678-9',
                        'RutReceptor' => '66666666-6',
                        'FchResol' => '2014-08-22',
                        'NroResol' => 80,
                        'TmstFirmaEnv' => '2024-01-15T10:00:00',
                        'SubTotDTE' => [
                            [
                                'TpoDTE' => 33,
                                'NroDTE' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function createMany(array $bags): DocumentEnvelopeInterface
    {
        if ($bags === []) {
            throw new DispatcherException(
                'At least one document must be specified to create the envelope.'
            );
        }

        foreach ($bags as $bag) {
            $this->documentBagManagerWorker->normalize($bag, true);
        }

        $envelope = new DocumentEnvelope();
        foreach ($bags as $bag) {
            $envelope->addDocument($bag);
        }
        $envelope->setCertificate($bags[0]->getCertificate());

        $this->normalize($envelope);

        return $envelope;
    }

    /**
     * {@inheritDoc}
     *
     * La carátula se arma solo si el sobre tiene el emisor con su autorización
     * del SII, el mandatario (que se obtiene del certificado digital) y el
     * receptor. Si falta alguno no se arma y el XML del sobre queda sin
     * carátula, sin que esto sea un error de la normalización: el esquema XSD
     * es quien valida el contenido del sobre, y esa validación es un paso
     * aparte que quien consume el sobre debe ejecutar siempre con
     * `validateSchema()`.
     */
    public function normalize(
        DocumentEnvelopeInterface $envelope
    ): DocumentEnvelopeInterface {
        $this->ensureSobreEnvio($envelope);
        $this->ensureDocuments($envelope);
        $this->ensureEmisor($envelope);
        $this->ensureMandatario($envelope);
        $this->ensureReceptor($envelope);
        $this->ensureCaratula($envelope);
        $this->ensureXmlDocument($envelope);

        return $envelope;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'load_xml',
        summary: 'Carga un sobre de documentos desde su XML',
        description: <<<'MD'
            Reconstruye un sobre de documentos tributarios electrónicos (`EnvioDTE` o `EnvioBOLETA`) a partir de su XML y entrega sus datos y los de cada documento que contiene. No valida el esquema ni la firma; para eso se usan `billing.document.dispatcher::validate_schema` y `billing.document.dispatcher::validate_signature`.

            ## Cómo se usa

            Se envía en `xml` el XML del sobre completo, codificado en base64, por ejemplo el que recibe un receptor. Si el XML está vacío o mal formado, la operación falla. También falla si el XML no es un sobre (`EnvioDTE` o `EnvioBOLETA`), por ejemplo un DTE suelto, y si el sobre contiene un documento sin un tipo de documento tributario válido.
            MD,
        parameters: [
            'xml' => [
                'description' => 'XML del sobre de documentos, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'Sobre cargado. `tag` es el tipo de sobre, `xml` su XML en base64 y `documents` los documentos que contiene, con la misma estructura que entrega `billing.document.builder::build`. `caratula` es la carátula del sobre, leída de su XML: `RutEmisor`, `RutEnvia`, `RutReceptor`, `FchResol`, `NroResol`, `TmstFirmaEnv` y `SubTotDTE` (cantidad de documentos por tipo).',
                'example' => [
                    'tag' => 'EnvioDTE',
                    'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                    'documents' => [
                        [
                            'document' => [
                                'Encabezado' => [
                                    'IdDoc' => [
                                        'TipoDTE' => 33,
                                        'Folio' => 1,
                                        'FchEmis' => '2024-01-15',
                                    ],
                                ],
                            ],
                            'document_id' => '76192083-9_T033F000000001',
                            'document_xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                        ],
                    ],
                    'caratula' => [
                        '@attributes' => [
                            'version' => '1.0',
                        ],
                        'RutEmisor' => '76192083-9',
                        'RutEnvia' => '12345678-9',
                        'RutReceptor' => '66666666-6',
                        'FchResol' => '2014-08-22',
                        'NroResol' => 80,
                        'TmstFirmaEnv' => '2024-01-15T10:00:00',
                        'SubTotDTE' => [
                            [
                                'TpoDTE' => 33,
                                'NroDTE' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function loadXml(XmlDocumentInterface|string $xml): DocumentEnvelopeInterface
    {
        if (is_string($xml)) {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($xml);
        } else {
            $xmlDocument = $xml;
        }

        // Solo se carga el XML de un sobre. Un DTE suelto u otro XML se
        // rechaza: sin esta revisión se obtendría un sobre cuyo XML no lo es.
        $root = $xmlDocument->getName();
        if (!in_array($root, ['EnvioDTE', 'EnvioBOLETA'], true)) {
            throw new DispatcherException([
                'The XML root {root} is not an envelope: EnvioDTE or EnvioBOLETA was expected.',
                'root' => $root,
            ]);
        }

        $envelope = new DocumentEnvelope();
        $envelope->setXmlDocument($xmlDocument);

        return $this->normalize($envelope);
    }

    /**
     * {@inheritDoc}
     *
     * Valida que los RUT de la carátula tengan dígito verificador válido, que
     * la fecha de la resolución no sea posterior a la fecha de la firma del
     * sobre y que la cantidad de documentos de cada tipo declarada en la
     * carátula coincida con la de los documentos del sobre. No valida los datos
     * de cada documento.
     */
    #[Operation(
        name: 'validate',
        summary: 'Valida los datos de un sobre de documentos',
        description: <<<'MD'
            Valida los datos del sobre de documentos (`EnvioDTE` o `EnvioBOLETA`): que los RUT de la carátula tengan dígito verificador válido, que la fecha de la resolución no sea posterior a la fecha de la firma del sobre, y que la cantidad de documentos de cada tipo declarada en la carátula coincida con la de los documentos del sobre. No valida los datos de cada documento, ni el esquema XSD ni la firma; para eso se usan `billing.document.validator::validate`, `billing.document.dispatcher::validate_schema` y `billing.document.dispatcher::validate_signature`.

            ## Cómo se usa

            Se envía en `source` el XML del sobre codificado en base64. Si el sobre es válido, la respuesta es el mismo documento; si no lo es, la operación falla con un error que indica la regla incumplida.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del sobre de documentos, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'El sobre es válido. Se devuelve su XML como árbol: la carátula y los documentos dentro de `SetDTE`, y la firma en `Signature`.',
                'example' => [
                    'EnvioDTE' => [
                        'SetDTE' => [
                            'Caratula' => [
                                'RutEmisor' => '76192083-9',
                                'RutEnvia' => '12345678-9',
                                'RutReceptor' => '66666666-6',
                                'FchResol' => '2014-08-22',
                                'NroResol' => '80',
                                'TmstFirmaEnv' => '2024-01-15T10:00:00',
                                'SubTotDTE' => [
                                    'TpoDTE' => '33',
                                    'NroDTE' => '1',
                                ],
                            ],
                            'DTE' => [
                                'Documento' => [
                                    'Encabezado' => [
                                        'IdDoc' => [
                                            'TipoDTE' => '33',
                                            'Folio' => '1',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'Signature' => [
                            'SignatureValue' => 'XOs4lQZku6vLebe6...',
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validate(
        DocumentEnvelopeInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        $xmlDocument = $this->getXmlDocument($source);

        $this->validateEnvelopeJob->execute($xmlDocument);

        return $xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_schema',
        summary: 'Valida el XML de un sobre contra su esquema',
        description: <<<'MD'
            Valida el XML de un sobre de documentos (`EnvioDTE` o `EnvioBOLETA`) contra su esquema XSD del SII. El esquema se toma del atributo `xsi:schemaLocation` que declara el propio XML.

            ## Cómo se usa

            Se envía en `source` el XML del sobre codificado en base64. Si cumple el esquema, la respuesta es el mismo documento; si no, la operación falla con un error que indica el esquema usado y la línea y la columna del problema.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del sobre de documentos, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'El XML cumple el esquema. Se devuelve el documento como árbol: la carátula y los documentos dentro de `SetDTE`, y la firma en `Signature`.',
                'example' => [
                    'EnvioDTE' => [
                        'SetDTE' => [
                            'Caratula' => [
                                'RutEmisor' => '76192083-9',
                                'RutEnvia' => '12345678-9',
                                'RutReceptor' => '66666666-6',
                                'FchResol' => '2014-08-22',
                                'NroResol' => '80',
                                'TmstFirmaEnv' => '2024-01-15T10:00:00',
                                'SubTotDTE' => [
                                    'TpoDTE' => '33',
                                    'NroDTE' => '1',
                                ],
                            ],
                            'DTE' => [
                                'Documento' => [
                                    'Encabezado' => [
                                        'IdDoc' => [
                                            'TipoDTE' => '33',
                                            'Folio' => '1',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'Signature' => [
                            'SignatureValue' => 'XOs4lQZku6vLebe6...',
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validateSchema(
        DocumentEnvelopeInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        $xmlDocument = $this->getXmlDocument($source);

        // Validar esquema del sobre de documentos (EnvioDTE y EnvioBOLETA).
        $schema = sprintf(
            '%s/resources/schemas/%s',
            dirname(__DIR__, 6),
            $xmlDocument->getSchema()
        );
        $this->xmlService->validate(
            $xmlDocument,
            $schema
        );

        return $xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_signature',
        summary: 'Valida las firmas electrónicas de un sobre',
        description: <<<'MD'
            Valida las firmas electrónicas (XML DSIG) de un sobre de documentos: la del propio sobre y la de cada documento que contiene. Entrega el resultado de cada firma y los datos del certificado de quien firmó.

            ## Cómo se usa

            Se envía en `source` el XML del sobre firmado, codificado en base64.

            La operación responde con éxito tanto si las firmas son válidas como si no lo son: el resultado de cada una está en su campo `valid`. Si es `false`, `error` explica el motivo (por ejemplo, que el contenido se modificó después de firmarlo).
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del sobre de documentos firmado, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'Lista con el resultado de cada firma encontrada en el XML. En cada elemento, `valid` indica si la firma es válida, `error` trae el motivo cuando no lo es (`null` si es válida), `signature` los datos de la firma y `signature.signer` los del certificado del firmante.',
                'example' => [
                    [
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
        ],
    )]
    public function validateSignature(
        DocumentEnvelopeInterface|XmlDocumentInterface|string $source
    ): array {
        $xmlDocument = $this->getXmlDocument($source);

        return $this->signatureService->validateXml($xmlDocument);
    }

    /**
     * Obtiene el documento XML a partir de la fuente.
     *
     * @param DocumentEnvelopeInterface|XmlDocumentInterface|string $source
     * @return XmlDocumentInterface
     */
    protected function getXmlDocument(
        DocumentEnvelopeInterface|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        if ($source instanceof DocumentEnvelopeInterface) {
            $xmlDocument = $source->requireXmlDocument();
        } elseif ($source instanceof XmlDocumentInterface) {
            $xmlDocument = $source;
        } else {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($source);
        }

        return $xmlDocument;
    }

    /**
     * Se asegura que exista un arreglo con las bolsas de los documentos
     * tributarios si no está definida y existe un documento XML en el sobre.
     *
     * @param DocumentEnvelopeInterface $envelope
     * @return void
     */
    protected function ensureDocuments(
        DocumentEnvelopeInterface $envelope
    ): void {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getDocuments() !== null || !$envelope->getSobreEnvio()) {
            return;
        }

        // Buscar documentos (DTE) en el XML del sobre y crear la bolsa de
        // cada documento para agregarla al listado de documentos que el sobre
        // gestiona.
        foreach ($envelope->getSobreEnvio()->getXmlDocumentos() as $xml) {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($xml);
            $bag = $this->documentBagManagerWorker->create($xmlDocument);
            $envelope->addDocument($bag);
        }
    }

    protected function ensureEmisor(DocumentEnvelopeInterface $envelope): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getEmisor() || !$envelope->getDocuments()) {
            return;
        }

        // Emisor del sobre se asume siempre como el emisor del primer documento.
        // Es una suposición válida siempre y cuando todos los documentos que se
        // envían sean del mismo emisor.
        $emisor = $envelope->getDocuments()[0]->requireEmisor();

        // Asignar la autorización de la carátula del documento XML al emisor.
        if ($envelope->getXmlDocument()) {
            $caratula = $envelope->getXmlDocument()->getReader(source: 'envelope XML');
            $RutEmisor = $caratula->getAsString('//Caratula/RutEmisor');
            $FchResol = $caratula->getAsString('//Caratula/FchResol');
            $NroResol = $caratula->getAsInt('//Caratula/NroResol', 0);
            if ($RutEmisor === $emisor->getRut() && $FchResol !== null) {
                // Cada documento tiene su propio objeto emisor, y el
                // documento usa la autorización de su emisor (por ejemplo en
                // su representación impresa), por eso se asigna a todos.
                $autorizacionDte = new AutorizacionDte($FchResol, $NroResol);
                foreach ($envelope->requireDocuments() as $document) {
                    $documentEmisor = $document->requireEmisor();
                    if ($documentEmisor->getRut() === $RutEmisor) {
                        $documentEmisor->setAutorizacionDte($autorizacionDte);
                    }
                }
            }
        }

        // Asignar el emisor al sobre.
        $envelope->setEmisor($emisor);
    }

    protected function ensureMandatario(DocumentEnvelopeInterface $envelope): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getMandatario() || !$envelope->getCertificate()) {
            return;
        }

        // Asignar como mandatario del sobre el usuario del certificado digital.
        $mandatario = new Mandatario($envelope->getCertificate()->getId());
        $envelope->setMandatario($mandatario);
    }

    protected function ensureReceptor(DocumentEnvelopeInterface $envelope): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getReceptor() || !$envelope->getDocuments()) {
            return;
        }

        // Asignar como receptor del sobre el receptor del primer documento.
        $receptor = $envelope->getDocuments()[0]->getReceptor();
        $envelope->setReceptor($receptor);
    }

    protected function ensureCaratula(DocumentEnvelopeInterface $envelope): void
    {
        // Ya está asignada, no hay nada que hacer.
        if ($envelope->getCaratula()) {
            return;
        }

        // Si el sobre ya tiene un XML propio (se cargó desde un XML existente
        // en vez de armarse desde cero) su carátula se lee directamente desde
        // ese XML en lugar de reconstruirla.
        if ($envelope->getXmlDocument()) {
            $caratula = $envelope->getXmlDocument()
                ->getReader(source: 'envelope XML')
                ->getMap('//Caratula')
            ;
            if ($caratula !== null) {
                $envelope->setCaratula($caratula);
            }

            return;
        }

        // Verificar si es necesario, y se puede, construir la carátula.
        if (
            !$envelope->getDocuments()
            || !$envelope->getEmisor()
            || !$envelope->getEmisor()->getAutorizacionDte()
            || !$envelope->getMandatario()
            || !$envelope->getReceptor()
        ) {
            return;
        }

        // Si se agregaron más tipos de documentos que los permitidos error.
        $SubTotDTE = $this->getResumen($envelope);

        $maximoTiposDocumentos = $envelope->getTipoSobre()->getMaxTiposDocumentos();
        if (isset($SubTotDTE[$maximoTiposDocumentos])) {
            throw new DispatcherException(
                [
                    'More document types were added than allowed in the envelope ({max}).',
                    'max' => $maximoTiposDocumentos,
                ]
            );
        }

        // Timestamp de la firma del sobre que se generará.
        $timestamp = date('Y-m-d\TH:i:s');

        // Crear datos de la carátula.
        $caratula = [
            '@attributes' => [
                'version' => '1.0',
            ],
            'RutEmisor' => $envelope->getEmisor()->getRut(),
            'RutEnvia' => $envelope->getMandatario()->getRun(),
            'RutReceptor' => $envelope->getReceptor()->getRut(),
            'FchResol' => $envelope->getEmisor()->getAutorizacionDte()->getFechaResolucion(),
            'NroResol' => $envelope->getEmisor()->getAutorizacionDte()->getNumeroResolucion(),
            'TmstFirmaEnv' => $timestamp,
            'SubTotDTE' => $SubTotDTE,
        ];

        // Asignar carátula al sobre.
        $envelope->setCaratula($caratula);
    }

    protected function ensureXmlDocument(
        DocumentEnvelopeInterface $envelope
    ): void {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getXmlDocument() || !$envelope->getDocuments()) {
            return;
        }

        // Generar la estructura base del documento XML del sobre de documentos.
        $data = [
            $envelope->getTipoSobre()->getTagXml() => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte '
                        . $envelope->getTipoSobre()->getSchema()
                    ,
                    'version' => '1.0',
                ],
                'SetDTE' => [
                    '@attributes' => [
                        'ID' => 'LibreDTE_SetDoc',
                    ],
                    'Caratula' => $envelope->getCaratula(),
                    'DTE' => '',
                ],
            ],
        ];

        // Generar el XML de los documentos que se deberán incorporar al sobre.
        $documents = [];
        foreach ($envelope->getDocuments() as $document) {
            $documents[] = trim(str_replace(
                [
                    '<?xml version="1.0" encoding="ISO-8859-1"?>',
                    '<?xml version="1.0"?>',
                ],
                '',
                $document->requireDocument()->getXml()
            ));
        }

        // Crear el documento XML del sobre (estructura base, sin DTE).
        $xmlDocument = $this->xmlService->encode($data);

        // Agregar los DTE dentro de SetDTE reemplazando el tag vacio DTE.
        $xmlBaseSobre = $xmlDocument->setEncoding('ISO-8859-1')->saveXML();
        $xmlSobre = str_replace('<DTE/>', implode("\n", $documents), $xmlBaseSobre);

        // Reemplazar el documento XML del sobre del envío con el string XML que
        // contiene ahora los documentos del envío.
        $xmlDocument->loadXML($xmlSobre);

        // Asignar el documento XML al sobre de documentos.
        $envelope->setXmlDocument($xmlDocument);

        // Si existe un certificado en el sobre de documento se firma el sobre.
        if ($envelope->getCertificate()) {
            $this->sign($envelope);
        }
    }

    protected function ensureSobreEnvio(
        DocumentEnvelopeInterface $envelope
    ): void {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getSobreEnvio() || !$envelope->getXmlDocument()) {
            return;
        }

        // Asignar la entidad del sobre del envío.
        $sobreEnvio = new SobreEnvio($envelope->getXmlDocument());
        $envelope->setSobreEnvio($sobreEnvio);
    }

    /**
     * Obtiene el resumen de los documentos que hay en el sobre.
     *
     * Esto se usa para para generar los tags del XML `SubTotDTE`.
     *
     * @param DocumentEnvelopeInterface $envelope
     * @return array<int, array{TpoDTE: int|string, NroDTE: int}> Arreglo con el resumen
     * de documentos por tipo.
     */
    protected function getResumen(DocumentEnvelopeInterface $envelope): array
    {
        // Contar los documentos por cada tipo.
        $subtotales = [];
        foreach ($envelope->requireDocuments() as $document) {
            $codigo = $document->requireTipoDocumento()->getCodigo();
            if (!isset($subtotales[$codigo])) {
                $subtotales[$codigo] = 0;
            }
            $subtotales[$codigo]++;
        }

        // Crear el resumen con los datos en el formato del tag `SubTotDTE`.
        $SubTotDTE = [];
        foreach ($subtotales as $tipo => $subtotal) {
            $SubTotDTE[] = [
                'TpoDTE' => $tipo,
                'NroDTE' => $subtotal,
            ];
        }

        // Entregar el resumen.
        return $SubTotDTE;
    }

    /**
     * Firma el sobre de documentos.
     *
     * @param DocumentEnvelopeInterface $envelope
     * @return void
     */
    protected function sign(DocumentEnvelopeInterface $envelope): void
    {
        // Corroborar que exista la carátula.
        $caratula = $envelope->getCaratula();
        if ($caratula === null) {
            throw new DispatcherException(
                'The envelope cover is not defined.'
            );
        }

        // El certificado digital para realizar la firma.
        $certificate = $envelope->requireCertificate();

        // Asignar marca de tiempo si no se pasó una.
        $timestamp = ArrayReader::fromArray($caratula, 'envelope cover')->requireString('TmstFirmaEnv');

        // Corroborar que el certificado esté vigente según el timestamp usado.
        if (!$certificate->isActive($timestamp)) {
            throw new DispatcherException([
                'Digital certificate for {id} is not valid at {time}; its validity range is from {from} to {to}.',
                'id' => $certificate->getID(),
                'time' => (new DateTime($timestamp))->format('d/m/Y H:i'),
                'from' => (new DateTime($certificate->getFrom()))->format('d/m/Y H:i'),
                'to' => (new DateTime($certificate->getTo()))->format('d/m/Y H:i'),
            ]);
        }

        // Obtener el documento XML a firmar.
        $xmlDocument = $envelope->requireXmlDocument();

        // Firmar el documento XML del sobre y retornar el XML firmado.
        $xmlSigned = $this->signatureService->signXml(
            $xmlDocument,
            $certificate,
            ['reference' => $envelope->getId()]
        );

        // Cargar XML en el documento y luego en la bolsa.
        $xmlDocument->loadXml($xmlSigned);
        $envelope->setXmlDocument($xmlDocument);
    }
}
