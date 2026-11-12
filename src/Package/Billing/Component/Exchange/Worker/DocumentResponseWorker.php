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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentResponseWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\RespuestaEnvio;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\DocumentResponseException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildRespuestaEnvioJob;
use Throwable;

/**
 * Worker "billing.exchange.document_response".
 *
 * Genera los XML de respuesta al intercambio de DTE:
 *   - `EnvioRecibos`: recibo de mercaderías o servicios (acción ERM).
 *   - `RespuestaDTE`: acuse de recibo del envío o resultado de validación.
 */
#[Worker(name: 'document_response', component: 'exchange', package: 'billing')]
class DocumentResponseWorker extends AbstractWorker implements DocumentResponseWorkerInterface
{
    public function __construct(
        private BuildEnvioRecibosJob $buildEnvioRecibosJob,
        private BuildRespuestaEnvioJob $buildRespuestaEnvioJob,
        private XmlServiceInterface $xmlService,
        private SignatureServiceInterface $signatureService,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Los datos de la bolsa no se validan contra el esquema XSD al construir el
     * documento: el esquema es quien valida el contenido, y esa validación es
     * un paso aparte que quien consume el documento debe ejecutar siempre con
     * `validateSchema()`. Un documento construido con datos incompletos se
     * firma igual, y es `validateSchema()` el que lo rechaza.
     */
    #[Operation(
        name: 'build_envio_recibos',
        summary: 'Construye el acuse de recibo de documentos recibidos',
        description: <<<'MD'
            Construye y firma el `EnvioRecibos`: el acuse de recibo de mercaderías o servicios que un receptor envía a su proveedor para los documentos que recibió (Ley 19.983). Cada recibo se firma individualmente y luego se firma el conjunto.

            ## Cómo se usa

            Se envía `bag` con `tipo` igual a `envio_recibos`, la carátula, la lista de documentos recibidos en `data` y el certificado digital con el que se firma. El XML construido está en la respuesta.

            Los datos no se validan contra el esquema al construir: un acuse con datos incompletos se firma igual. Para comprobarlo se usa siempre `billing.exchange.document_response::validate_schema`.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del acuse de recibo.

                    | Campo | Descripción |
                    |---|---|
                    | `tipo` | Obligatorio. Debe ser `envio_recibos`. |
                    | `caratula` | Carátula. `RutResponde` es el RUT de quien responde (el receptor), `RutRecibe` el de quien recibe el acuse (el emisor de los documentos) y, opcionalmente, `NmbContacto`, `FonoContacto` y `MailContacto`, los datos de contacto. |
                    | `data` | Lista de los documentos recibidos, uno por recibo. Cada uno trae `TipoDoc`, `Folio`, `FchEmis` (`AAAA-MM-DD`), `RUTEmisor`, `RUTRecep`, `MntTotal`, `Recinto` (lugar donde se recibió) y `RutFirma` (RUT de quien firma el recibo). |
                    | `certificate` | Certificado digital con el que se firma. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. |
                    MD,
                'example' => [
                    'tipo' => 'envio_recibos',
                    'caratula' => [
                        'RutResponde' => '76192083-9',
                        'RutRecibe' => '88888888-8',
                    ],
                    'data' => [
                        [
                            'TipoDoc' => 33,
                            'Folio' => 1,
                            'FchEmis' => '2024-01-15',
                            'RUTEmisor' => '88888888-8',
                            'RUTRecep' => '76192083-9',
                            'MntTotal' => 100000,
                            'Recinto' => 'Oficina central',
                            'RutFirma' => '76192083-9',
                        ],
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
                'description' => 'Acuse construido y firmado. Es el XML del `EnvioRecibos` como árbol: `SetRecibos` con la carátula (`TmstFirmaEnv` es la fecha y hora de la firma) y cada recibo, con su `Declaracion` legal y su firma.',
                'example' => [
                    'EnvioRecibos' => [
                        'SetRecibos' => [
                            'Caratula' => [
                                'RutResponde' => '76192083-9',
                                'RutRecibe' => '88888888-8',
                                'TmstFirmaEnv' => '2024-01-15T10:00:00',
                            ],
                            'Recibo' => [
                                'DocumentoRecibo' => [
                                    'TipoDoc' => '33',
                                    'Folio' => '1',
                                    'FchEmis' => '2024-01-15',
                                    'RUTEmisor' => '88888888-8',
                                    'RUTRecep' => '76192083-9',
                                    'MntTotal' => '100000',
                                    'Recinto' => 'Oficina central',
                                    'RutFirma' => '76192083-9',
                                    'Declaracion' => 'El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4, y la letra c) del Art. 5 de la Ley 19.983, acredita que la entrega de mercaderias o servicio(s) prestado(s) ha(n) sido recibido(s).',
                                    'TmstFirmaRecibo' => '2024-01-15T10:00:00',
                                ],
                                'Signature' => [
                                    'SignatureValue' => 'eNiW6a1yU0kjO7tPCkjbWSmhCPs0...',
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
    public function buildEnvioRecibos(ExchangeDocumentBag $bag): EnvioRecibos
    {
        try {
            $document = $this->buildEnvioRecibosJob->build($bag);
            $bag->setDocument($document);

            return $document;
        } catch (Throwable $e) {
            throw new DocumentResponseException(
                message: [
                    'Could not build EnvioRecibos: {detail}',
                    'detail' => $e,
                ],
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     *
     * Los datos de la bolsa no se validan contra el esquema XSD al construir el
     * documento: el esquema es quien valida el contenido, y esa validación es
     * un paso aparte que quien consume el documento debe ejecutar siempre con
     * `validateSchema()`. Un documento construido con datos incompletos se
     * firma igual, y es `validateSchema()` el que lo rechaza.
     */
    #[Operation(
        name: 'build_respuesta_envio',
        summary: 'Construye la respuesta a un envío de documentos',
        description: <<<'MD'
            Construye y firma la `RespuestaDTE`: la respuesta que un receptor entrega a su proveedor sobre los documentos que recibió. Puede ser la confirmación de recepción del envío (`RecepcionEnvio`) o el resultado de la aprobación comercial de cada documento (`ResultadoDTE`). El resultado se firma.

            ## Cómo se usa

            Se envía `bag` con `tipo` igual a `respuesta_envio`, la carátula, el certificado digital y, en `data`, una de dos listas: `recepcion_envio` o `resultado_dte`. El XML construido está en la respuesta.

            Los datos no se validan contra el esquema al construir: una respuesta con datos incompletos se firma igual. Para comprobarlo se usa siempre `billing.exchange.document_response::validate_schema`.

            ## Estados

            `EstadoRecepEnv` (estado de recepción del envío, en `recepcion_envio`):

            | Valor | Significado |
            |---|---|
            | `0` | Envío recibido conforme |
            | `1` | Envío rechazado: error de esquema |
            | `2` | Envío rechazado: error de firma |
            | `3` | Envío rechazado: RUT del receptor no corresponde |
            | `90` | Envío rechazado: archivo repetido |
            | `91` | Envío rechazado: archivo ilegible |
            | `99` | Envío rechazado: otros |

            `EstadoRecepDTE` (estado de recepción de cada documento, en `RecepcionDTE`):

            | Valor | Significado |
            |---|---|
            | `0` | Documento recibido OK |
            | `1` | No recibido: error de firma |
            | `2` | No recibido: error en el RUT del emisor |
            | `3` | No recibido: error en el RUT del receptor |
            | `4` | No recibido: documento repetido |
            | `99` | No recibido: otros |

            `EstadoDTE` (aprobación comercial, en `resultado_dte`):

            | Valor | Significado |
            |---|---|
            | `0` | Documento aceptado OK |
            | `1` | Documento aceptado con discrepancias |
            | `2` | Documento rechazado |
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos de la respuesta.

                    | Campo | Descripción |
                    |---|---|
                    | `tipo` | Obligatorio. Debe ser `respuesta_envio`. |
                    | `caratula` | Carátula. `RutResponde` es el RUT de quien responde, `RutRecibe` el de quien recibe la respuesta, `IdRespuesta` un número que identifica la respuesta y, opcionalmente, `NmbContacto`, `FonoContacto` y `MailContacto`. `NroDetalles` se calcula. |
                    | `data` | Debe tener una de estas dos claves: `recepcion_envio` o `resultado_dte`. |
                    | `certificate` | Certificado digital con el que se firma. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. |

                    `data.resultado_dte` es una lista; cada elemento trae `TipoDTE`, `Folio`, `FchEmis`, `RUTEmisor`, `RUTRecep`, `MntTotal`, `CodEnvio` (número que identifica el envío), `EstadoDTE`, `EstadoDTEGlosa` y, opcionalmente, `CodRchDsc` (código del motivo de rechazo o discrepancia).

                    `data.recepcion_envio` es una lista; cada elemento trae `NmbEnvio`, `FchRecep`, `CodEnvio`, `EnvioDTEID`, `Digest`, `RutEmisor`, `RutReceptor`, `EstadoRecepEnv`, `RecepEnvGlosa`, `NroDTE` y `RecepcionDTE` (la lista de documentos con su estado de recepción).
                    MD,
                'example' => [
                    'tipo' => 'respuesta_envio',
                    'caratula' => [
                        'RutResponde' => '76192083-9',
                        'RutRecibe' => '88888888-8',
                        'IdRespuesta' => 1,
                    ],
                    'data' => [
                        'resultado_dte' => [
                            [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                                'FchEmis' => '2024-01-15',
                                'RUTEmisor' => '88888888-8',
                                'RUTRecep' => '76192083-9',
                                'MntTotal' => 100000,
                                'CodEnvio' => 1,
                                'EstadoDTE' => 0,
                                'EstadoDTEGlosa' => 'ACEPTADO OK',
                            ],
                        ],
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
                'description' => 'Respuesta construida y firmada. Es el XML de la `RespuestaDTE` como árbol: `Resultado` con la carátula (`TmstFirmaResp` es la fecha y hora de la firma) y el resultado de cada documento o del envío, y la firma en `Signature`.',
                'example' => [
                    'RespuestaDTE' => [
                        'Resultado' => [
                            'Caratula' => [
                                'RutResponde' => '76192083-9',
                                'RutRecibe' => '88888888-8',
                                'IdRespuesta' => '1',
                                'NroDetalles' => '1',
                                'TmstFirmaResp' => '2024-01-15T10:00:00',
                            ],
                            'ResultadoDTE' => [
                                'TipoDTE' => '33',
                                'Folio' => '1',
                                'FchEmis' => '2024-01-15',
                                'RUTEmisor' => '88888888-8',
                                'RUTRecep' => '76192083-9',
                                'MntTotal' => '100000',
                                'CodEnvio' => '1',
                                'EstadoDTE' => '0',
                                'EstadoDTEGlosa' => 'ACEPTADO OK',
                            ],
                        ],
                        'Signature' => [
                            'SignatureValue' => 'hZLU7mMg3MPc9683q5HS2wNf...',
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function buildRespuestaEnvio(ExchangeDocumentBag $bag): RespuestaEnvio
    {
        try {
            $document = $this->buildRespuestaEnvioJob->build($bag);
            $bag->setDocument($document);

            return $document;
        } catch (Throwable $e) {
            throw new DocumentResponseException(
                message: [
                    'Could not build RespuestaDTE: {detail}',
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
        summary: 'Valida el XML de un acuse o respuesta contra su esquema',
        description: <<<'MD'
            Valida el XML de un documento de respuesta (`EnvioRecibos` o `RespuestaDTE`) contra su esquema XSD del SII.

            ## Cómo se usa

            Se envía en `source` el XML construido con `billing.exchange.document_response::build_envio_recibos` o `billing.exchange.document_response::build_respuesta_envio`, codificado en base64. Si cumple el esquema, la respuesta es el mismo documento; si no, la operación falla con un error que indica el esquema usado y la línea y la columna del problema. También falla si no se puede determinar el esquema del documento.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del acuse (`EnvioRecibos`) o de la respuesta (`RespuestaDTE`), codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => 'El XML cumple el esquema. Se devuelve el documento como árbol.',
                'example' => [
                    'EnvioRecibos' => [
                        'SetRecibos' => [
                            'Caratula' => [
                                'RutResponde' => '76192083-9',
                                'RutRecibe' => '88888888-8',
                                'TmstFirmaEnv' => '2024-01-15T10:00:00',
                            ],
                            'Recibo' => [
                                'DocumentoRecibo' => [
                                    'TipoDoc' => '33',
                                    'Folio' => '1',
                                    'FchEmis' => '2024-01-15',
                                    'RUTEmisor' => '88888888-8',
                                    'RUTRecep' => '76192083-9',
                                    'MntTotal' => '100000',
                                    'Recinto' => 'Oficina central',
                                    'RutFirma' => '76192083-9',
                                    'TmstFirmaRecibo' => '2024-01-15T10:00:00',
                                ],
                                'Signature' => [
                                    'SignatureValue' => 'qA5Gk+dDePm7TdqjUqNP...',
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
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): XmlDocumentInterface {
        $xmlDocument = $this->toXmlDocument($source);

        if ($source instanceof AbstractExchangeDocument) {
            $schemaFile = $source->getSchema();
        } else {
            $root = $xmlDocument->getDomDocument()->documentElement->localName ?? '';
            $schemaFile = match ($root) {
                'EnvioRecibos' => 'EnvioRecibos_v10.xsd',
                'RespuestaDTE' => 'RespuestaEnvioDTE_v10.xsd',
                default => throw new DocumentResponseException([
                    'Root element "{root}" is not recognized as a response document.',
                    'root' => $root,
                ]),
            };
        }

        $schema = dirname(__DIR__, 6) . '/resources/schemas/' . $schemaFile;
        $this->xmlService->validate($xmlDocument, $schema);

        return $xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_signature',
        summary: 'Valida las firmas de un acuse o respuesta',
        description: <<<'MD'
            Valida las firmas electrónicas (XML DSIG) de un documento de respuesta (`EnvioRecibos` o `RespuestaDTE`). Un `EnvioRecibos` tiene varias firmas: una por cada recibo y la del conjunto; se entrega el resultado de todas.

            ## Cómo se usa

            Se envía en `source` el XML firmado, codificado en base64. Si el XML está mal formado o no contiene firmas, la operación falla.

            La operación responde con éxito tanto si las firmas son válidas como si no lo son: el resultado de cada una está en su campo `valid`. Si es `false`, `error` explica el motivo.
            MD,
        parameters: [
            'source' => [
                'description' => 'XML del acuse (`EnvioRecibos`) o de la respuesta (`RespuestaDTE`) firmado, codificado en base64.',
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
                            'reference' => 'LibreDTE_T33F1',
                            'canonicalization_method' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                            'signature_method' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                            'digest_method' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                            'digest_value' => 'iqCtA9K9784IzklwouBBZOhavek=',
                            'signature_value' => 'qA5Gk+dDePm7TdqjUqNPpb+SikpS8wZr...',
                            'modulus' => 'xA9E+qja3zDCN0ll8ul38aqgm0KL9jDO...',
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
                                'modulus' => 'xA9E+qja3zDCN0ll8ul38aqgm0KL9jDO...',
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
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): array {
        if ($source instanceof AbstractExchangeDocument) {
            $source = $source->saveXml();
        }

        return $this->signatureService->validateXml($source);
    }

    /**
     * Convierte la fuente en un `XmlDocument`.
     */
    private function toXmlDocument(
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): XmlDocument {
        if ($source instanceof AbstractExchangeDocument) {
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
