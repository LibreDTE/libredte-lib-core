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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Contract\AecWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Aec;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Exception\OwnershipTransferException;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Support\AecBag;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job\BuildAecJob;
use Throwable;

/**
 * Worker "billing.ownership_transfer.aec".
 *
 * Genera y valida el Archivo Electrónico de Cesión (AEC).
 */
#[Worker(name: 'aec', component: 'ownership_transfer', package: 'billing')]
class AecWorker extends AbstractWorker implements AecWorkerInterface
{
    public function __construct(
        private BuildAecJob $buildAecJob,
        private XmlServiceInterface $xmlService,
        private SignatureServiceInterface $signatureService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'build',
        summary: 'Construye el archivo de cesión de un crédito (AEC)',
        description: <<<'MD'
            Construye y firma el AEC (Archivo Electrónico de Cesión): el documento con el que se cede a un tercero (el cesionario, por ejemplo una empresa de factoring) el crédito de una factura. Contiene el documento cedido (`DTECedido`), la cesión (`Cesion`) y el conjunto, y cada parte se firma.

            ## Cómo se usa

            Se envía `bag` con el documento que se cede, el cedente, el cesionario, los datos de la cesión y el certificado digital con el que se firma. La respuesta trae el AEC y su XML en base64.

            Hay dos casos, que se distinguen por lo que se envía en `source`:

            - **Primera cesión**: `source` es el XML de la factura cedida (el DTE).
            - **Re-cesión**: `source` es el XML de un AEC ya construido. Se agrega una nueva cesión a las que ya tenía, con el número de secuencia siguiente, y se vuelve a firmar el conjunto.

            Los datos no se validan contra el esquema al construir; para comprobarlo se usa `billing.ownership_transfer.aec::validate_schema`.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos de la cesión.

                    | Campo | Descripción |
                    |---|---|
                    | `source` | Obligatorio. XML codificado en base64: el del documento que se cede (primera cesión) o el de un AEC (re-cesión). |
                    | `cedente` | Obligatorio. Quien cede el crédito: `RUT`, `RazonSocial`, `Direccion`, `eMail` y `RUTAutorizado` (`RUT` y `Nombre` de la persona autorizada para ceder). |
                    | `cesionario` | Obligatorio. Quien recibe el crédito: `RUT`, `RazonSocial`, `Direccion` y `eMail`. |
                    | `cesion` | Obligatorio. Datos de la cesión: `MontoCesion` (por defecto, el monto total del documento) y `UltimoVencimiento` (`AAAA-MM-DD`). |
                    | `certificate` | Certificado digital con el que se firma. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. |
                    MD,
                'example' => [
                    'source' => '',
                    'cedente' => [
                        'RUT' => '76192083-9',
                        'RazonSocial' => 'SASCO SpA',
                        'Direccion' => 'Santa Cruz, Chile',
                        'eMail' => 'cedente@example.com',
                        'RUTAutorizado' => [
                            'RUT' => '76192083-9',
                            'Nombre' => 'Administrador',
                        ],
                    ],
                    'cesionario' => [
                        'RUT' => '76354771-K',
                        'RazonSocial' => 'Factoring S.A.',
                        'Direccion' => 'Providencia, Santiago',
                        'eMail' => 'factoring@example.com',
                    ],
                    'cesion' => [
                        'MontoCesion' => 119000,
                        'UltimoVencimiento' => '2024-02-14',
                    ],
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => 'AEC construido y firmado. `AEC` es su contenido como árbol: `DocumentoAEC` con la carátula (`RutCedente`, `RutCesionario` y `TmstFirmaEnvio`, la fecha y hora de la firma), el documento cedido (`DTECedido`) y la cesión (`Cesion`), y la firma del conjunto en `Signature`. `xml` es el XML completo en base64.',
                'example' => [
                    'AEC' => [
                        'DocumentoAEC' => [
                            'Caratula' => [
                                'RutCedente' => '76192083-9',
                                'RutCesionario' => '76354771-K',
                                'TmstFirmaEnvio' => '2024-01-15T10:00:00',
                            ],
                            'Cesiones' => [
                                'DTECedido' => [
                                    'DocumentoDTECedido' => [
                                        'DTE' => [
                                            'Documento' => [
                                                'Encabezado' => [
                                                    'IdDoc' => [
                                                        'TipoDTE' => '33',
                                                        'Folio' => '1',
                                                        'FchEmis' => '2024-01-15',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'TmstFirma' => '2024-01-15T10:00:00',
                                    ],
                                    'Signature' => [
                                        'SignatureValue' => 'qA5Gk+dDePm7TdqjUqNP...',
                                    ],
                                ],
                                'Cesion' => [
                                    'DocumentoCesion' => [
                                        'SeqCesion' => '1',
                                        'IdDTE' => [
                                            'TipoDTE' => '33',
                                            'RUTEmisor' => '76192083-9',
                                            'RUTReceptor' => '66666666-6',
                                            'Folio' => '1',
                                            'FchEmis' => '2024-01-15',
                                            'MntTotal' => '119000',
                                        ],
                                        'MontoCesion' => '119000',
                                        'UltimoVencimiento' => '2024-02-14',
                                        'TmstCesion' => '2024-01-15T10:00:00',
                                    ],
                                    'Signature' => [
                                        'SignatureValue' => 'hZLU7mMg3MPc9683q5HS...',
                                    ],
                                ],
                            ],
                        ],
                        'Signature' => [
                            'SignatureValue' => 'GffJnGDDPNUxfXKFOgpFgDTQhxfJinl1...',
                        ],
                    ],
                    'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pgo8...',
                ],
            ],
        ],
    )]
    public function build(AecBag $bag): Aec
    {
        try {
            return $this->buildAecJob->build($bag);
        } catch (Throwable $e) {
            throw new OwnershipTransferException(
                message: [
                    'Could not build the AEC: {detail}',
                    'detail' => $e,
                ],
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_schema',
        summary: 'Valida el XML de un AEC contra su esquema',
        description: <<<'MD'
            Valida el XML de un AEC (Archivo Electrónico de Cesión) contra su esquema XSD del SII.

            ## Cómo se usa

            Se envía en `source` el XML del AEC codificado en base64, por ejemplo el que entrega `billing.ownership_transfer.aec::build` en `xml`. Si cumple el esquema, la respuesta es el mismo documento; si no, la operación falla con un error que indica el esquema usado y la línea y la columna del problema. También falla si no se puede determinar el esquema del documento.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del AEC, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'El XML cumple el esquema. Se devuelve el AEC como árbol.',
                'example' => [
                    'AEC' => [
                        'DocumentoAEC' => [
                            'Caratula' => [
                                'RutCedente' => '76192083-9',
                                'RutCesionario' => '76354771-K',
                                'TmstFirmaEnvio' => '2024-01-15T10:00:00',
                            ],
                            'Cesiones' => [
                                'DTECedido' => [
                                    'Signature' => [
                                        'SignatureValue' => 'qA5Gk+dDePm7TdqjUqNP...',
                                    ],
                                ],
                                'Cesion' => [
                                    'Signature' => [
                                        'SignatureValue' => 'hZLU7mMg3MPc9683q5HS...',
                                    ],
                                ],
                            ],
                        ],
                        'Signature' => [
                            'SignatureValue' => 'GffJnGDDPNUxfXKFOgpFgDTQhxfJinl1...',
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function validateSchema(
        Aec|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        $xmlDocument = $this->toXmlDocument($source);

        $schema = dirname(__DIR__, 6) . '/resources/schemas/AEC_v10.xsd';
        $this->xmlService->validate($xmlDocument, $schema);

        return $xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_signature',
        summary: 'Valida las firmas de un AEC',
        description: <<<'MD'
            Valida las firmas electrónicas (XML DSIG) de un AEC (Archivo Electrónico de Cesión): la del documento cedido (`DTECedido`), la de la cesión (`Cesion`) y la del conjunto (`AEC`). Se entrega el resultado de cada una.

            ## Cómo se usa

            Se envía en `source` el XML del AEC firmado, codificado en base64. Si el XML está mal formado o no contiene firmas, la operación falla.

            La operación responde con éxito tanto si las firmas son válidas como si no lo son: el resultado de cada una está en su campo `valid`. Si es `false`, `error` explica el motivo.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del AEC firmado, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'Lista con el resultado de cada firma encontrada. En cada elemento, `valid` indica si la firma es válida, `error` trae el motivo cuando no lo es (`null` si es válida), `signature` los datos de la firma y `signature.signer` los del certificado del firmante.',
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
                                'id' => '76192083-9',
                                'name' => 'Empresa de Ejemplo SpA',
                                'email' => 'contacto@example.com',
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
        Aec|XmlDocumentInterface|string $source
    ): array {
        if ($source instanceof Aec) {
            $source = $source->saveXml();
        }

        return $this->signatureService->validateXml($source);
    }

    /**
     * Convierte la fuente en un `XmlDocument`.
     */
    private function toXmlDocument(Aec|XmlDocumentInterface|string $source): XmlDocument
    {
        if ($source instanceof Aec) {
            $xml = $source->saveXml();
        } elseif ($source instanceof XmlDocumentInterface) {
            $xml = $source->saveXml();
        } else {
            $xml = $source;
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return $xmlDocument;
    }
}
