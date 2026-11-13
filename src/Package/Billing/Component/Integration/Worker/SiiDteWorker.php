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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDteWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\CheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\RequestXmlDocumentSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\SendXmlDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentSignatureResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\CheckXmlDocumentSentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\RequestXmlDocumentSentStatusByEmailJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\SendXmlDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\ValidateDocumentJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiDte\Job\ValidateDocumentSignatureJob;

/**
 * Clase del worker de DTE del SII.
 */
#[Worker(name: 'sii_dte', component: 'integration', package: 'billing')]
class SiiDteWorker extends AbstractWorker implements SiiDteWorkerInterface
{
    private const REQUEST_DESCRIPTION = <<<'MD'
        Datos de la conexión al SII.

        | Campo | Descripción |
        |---|---|
        | `certificate` | Obligatorio. Certificado digital con el que se autentica en el SII. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. |
        | `options.environment` | Ambiente del SII: `0` producción (valor por defecto) o `1` certificación (pruebas). |
        | `options.retries` | Cantidad máxima de intentos ante fallas de conexión, de `0` a `10`; por defecto `10`. Con `0` no se hace ningún intento. Con muchos intentos la espera crece de forma exponencial. |
        | `options.verify_ssl` | Verifica el certificado SSL del SII; por defecto `true`. No se recomienda desactivarlo. |
        | `options.token` | Cómo se guarda el token de autenticación: `cache` (`memory` o `filesystem`; por defecto `memory`), `key` y `ttl` (segundos que se conserva; por defecto `2700`). |
        MD;

    public function __construct(
        private CheckXmlDocumentSentStatusJob $checkXmlDocumentSentStatusJob,
        private RequestXmlDocumentSentStatusByEmailJob $requestXmlDocumentSentStatusByEmailJob,
        private SendXmlDocumentJob $sendXmlDocumentJob,
        private ValidateDocumentJob $validateDocumentJob,
        private ValidateDocumentSignatureJob $validateDocumentSignatureJob
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'send_xml_document',
        summary: 'Envía un documento XML al SII',
        description: <<<'MD'
            Envía al SII un archivo XML con documentos tributarios electrónicos (el sobre `EnvioDTE`) y entrega el número de seguimiento (Track ID) del envío. Sirve para cualquier documento tributario electrónico, excepto las boletas.

            ## Cómo se usa

            Se envían los datos de conexión en `request`, el XML en `doc` y el RUT de la empresa emisora en `company`. Con el `track_id` de la respuesta se consulta el estado del envío con `billing.integration.sii_dte::check_xml_document_sent_status`.

            La operación falla si alguno de los RUT es inválido, si la solicitud no incluye un certificado, si falla la autenticación en el SII, o si la empresa no está autorizada para enviar documentos al SII.

            Detalle del servicio del SII: [Envío Automático de Documentos Tributarios Electrónicos](https://www.sii.cl/factura_electronica/factura_mercado/envio.pdf).
            MD,
        parameters: [
            'request' => [
                'description' => self::REQUEST_DESCRIPTION,
                'example' => [
                        'certificate' => [
                            'certificate' => '',
                            'privateKey' => '',
                        ],
                        'options' => [
                            'environment' => 1,
                        ],
                    ],
            ],
            'doc' => [
                'description' => 'XML del sobre de documentos (`EnvioDTE`) que se envía, codificado en base64. Se obtiene con `billing.document.dispatcher::create`.',
                'example' => '',
            ],
            'company' => [
                'description' => 'RUT de la empresa emisora, con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
            'compress' => [
                'description' => 'Si es `true`, el XML se envía comprimido. Por defecto `false`.',
                'example' => false,
            ],
            'retries' => [
                'description' => 'Cantidad máxima de intentos al enviar. Si no se indica, se usa `request.options.retries`.',
                'example' => 3,
            ],
        ],
        results: [
            'success' => [
                'description' => 'Envío recibido por el SII. `track_id` es el número de seguimiento que asigna el SII al envío.',
                'example' => [
                    'track_id' => 1234567890,
                ],
            ],
        ],
    )]
    public function sendXmlDocument(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        bool $compress = false,
        ?int $retries = null
    ): SendXmlDocumentResponse {
        return $this->sendXmlDocumentJob->send(
            $request,
            $doc,
            $company,
            $compress,
            $retries
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'check_xml_document_sent_status',
        summary: 'Consulta el estado de un envío de documentos al SII',
        description: <<<'MD'
            Obtiene el estado actualizado de un envío de documentos al SII, con el detalle por cada tipo de documento. Aplica a los envíos de documentos tributarios electrónicos (sobre `EnvioDTE`), excepto las boletas.

            ## Cómo se usa

            Se envían los datos de conexión en `request`, el `trackId` que entregó `billing.integration.sii_dte::send_xml_document` y el RUT de la empresa emisora en `company`. El estado puede no ser el final: en ese caso se debe repetir la consulta más adelante.

            ## Estados (`status`)

            | Valor | Significado |
            |---|---|
            | `RSC` | Rechazado por error en el esquema |
            | `SOK` | Esquema validado |
            | `CRT` | Carátula OK |
            | `RFR` | Rechazado por error en la firma |
            | `FOK` | Firma del envío validada |
            | `PDR` | Envío en proceso |
            | `RCT` | Rechazado por error en la carátula |
            | `EPR` | Envío procesado |
            | `LNC` | Tipo de envío de libro no corresponde |
            | `LSO` | Esquema del envío de libro correcto |
            | `LOK` | Envío de libro aceptado |

            Cuando el SII responde con un error, `status` trae el código del error, `error` es `true` y `description` explica el motivo.

            Detalle del servicio del SII: [Consulta de Estado de Upload de DTE](https://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf).
            MD,
        parameters: [
            'request' => [
                'description' => self::REQUEST_DESCRIPTION,
                'example' => [
                        'certificate' => [
                            'certificate' => '',
                            'privateKey' => '',
                        ],
                        'options' => [
                            'environment' => 1,
                        ],
                    ],
            ],
            'trackId' => [
                'description' => 'Número de seguimiento (Track ID) que asignó el SII al envío.',
                'example' => 1234567890,
            ],
            'company' => [
                'description' => 'RUT de la empresa emisora, con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Estado del envío. `status` y `description` son los que entrega el SII, `error` indica si es un error y `resume` cuenta los documentos del envío: informados (`reported`), aceptados (`accepted`), rechazados (`rejected`) y con reparos (`repairs`). `documents` trae el mismo detalle por tipo de documento, indexado por el código del tipo. `query_number` y `query_datetime` son el número y la fecha de la atención del SII, cuando los informa.
                    MD,
                'example' => [
                    'query_number' => '2024011500123',
                    'query_datetime' => '2024-01-15T10:00:00',
                    'track_id' => 1234567890,
                    'company' => '76192083-9',
                    'status' => 'EPR',
                    'error' => false,
                    'description' => 'Envío Procesado',
                    'resume' => [
                        'reported' => 1,
                        'accepted' => 1,
                        'rejected' => 0,
                        'repairs' => 0,
                    ],
                    'documents' => [
                        '33' => [
                            'type' => 33,
                            'reported' => 1,
                            'accepted' => 1,
                            'rejected' => 0,
                            'repairs' => 0,
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function checkXmlDocumentSentStatus(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): CheckXmlDocumentSentStatusResponse {
        return $this->checkXmlDocumentSentStatusJob->checkSentStatus(
            $request,
            $trackId,
            $company
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'request_xml_document_sent_status_by_email',
        summary: 'Solicita al SII el estado de un envío por correo',
        description: <<<'MD'
            Solicita al SII que le envíe por correo electrónico el estado de un envío de documentos. La ventaja respecto de `billing.integration.sii_dte::check_xml_document_sent_status` es que el correo incluye el detalle de los rechazos, que la consulta del estado no entrega.

            ## Cómo se usa

            Se envían los datos de conexión en `request`, el `trackId` del envío y el RUT de la empresa emisora en `company`. El correo se envía a la dirección que la empresa tiene configurada en el SII; no se puede indicar otra. Aplica a los envíos de documentos tributarios electrónicos (sobre `EnvioDTE`), excepto las boletas.

            Detalle del servicio del SII: [Solicitud de Reenvío de Correo de Validación de DTE](https://www.sii.cl/factura_electronica/factura_mercado/OIFE2005_wsDTECorreo_MDE.pdf).
            MD,
        parameters: [
            'request' => [
                'description' => self::REQUEST_DESCRIPTION,
                'example' => [
                        'certificate' => [
                            'certificate' => '',
                            'privateKey' => '',
                        ],
                        'options' => [
                            'environment' => 1,
                        ],
                    ],
            ],
            'trackId' => [
                'description' => 'Número de seguimiento (Track ID) que asignó el SII al envío.',
                'example' => 1234567890,
            ],
            'company' => [
                'description' => 'RUT de la empresa emisora, con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Respuesta del SII a la solicitud. `status` y `description` son los que entrega el SII; el ejemplo muestra la respuesta cuando el usuario autenticado no tiene permisos sobre la empresa.
                    MD,
                'example' => [
                    'company' => '76192083-9',
                    'track_id' => 1234567890,
                    'status' => '106',
                    'description' => 'Usuario autenticado no tiene permisos sobre Empresa...',
                ],
            ],
        ],
    )]
    public function requestXmlDocumentSentStatusByEmail(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): RequestXmlDocumentSentStatusByEmailResponse {
        return $this->requestXmlDocumentSentStatusByEmailJob->requestEmail(
            $request,
            $trackId,
            $company
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_document',
        summary: 'Valida un documento aceptado por el SII',
        description: <<<'MD'
            Consulta al SII si un documento tributario electrónico fue aceptado y si los datos informados (tipo, folio, fecha, monto y receptor) coinciden con los que el SII tiene registrados. Aplica a cualquier documento tributario electrónico, excepto las boletas.

            ## Cómo se usa

            Se envían los datos de conexión en `request` y los datos del documento. Si se quiere comprobar además la firma del documento, se usa `billing.integration.sii_dte::validate_document_signature`.

            ## Estados (`status`)

            | Valor | Significado |
            |---|---|
            | `DOK` | Documento recibido por el SII. Datos coinciden con los registrados. |
            | `DNK` | Documento recibido por el SII pero datos no coinciden con los registrados. |
            | `FAU` | Folio del documento no recibido por el SII. |
            | `FNA` | Folio no autorizado por el SII. |
            | `FAN` | Folio anulado antes de ser enviado al SII. |
            | `EMP` | Empresa no autorizada a emitir documentos tributarios electrónicos. |
            | `TMD` | Existe nota de débito que modifica el texto del documento. |
            | `TMC` | Existe nota de crédito que modifica el texto del documento. |
            | `MMD` | Existe nota de débito que modifica los montos del documento. |
            | `MMC` | Existe nota de crédito que modifica los montos del documento. |
            | `AND` | Existe nota de débito que anula el documento. |
            | `ANC` | Existe nota de crédito que anula el documento. |

            Detalle del servicio del SII: [Consulta de Estado de DTE](https://www.sii.cl/factura_electronica/factura_mercado/estado_dte.pdf).
            MD,
        parameters: [
            'request' => [
                'description' => self::REQUEST_DESCRIPTION,
                'example' => [
                        'certificate' => [
                            'certificate' => '',
                            'privateKey' => '',
                        ],
                        'options' => [
                            'environment' => 1,
                        ],
                    ],
            ],
            'company' => [
                'description' => 'RUT de la empresa emisora, con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
            'document' => [
                'description' => <<<'MD'
                        Código del tipo de documento tributario electrónico, por ejemplo `33` (factura electrónica). Los códigos válidos están en el catálogo: operación `system.repository.catalog::find_all` con `repository` igual a `libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface`.
                        MD,
                'example' => 33,
            ],
            'number' => [
                'description' => 'Folio del documento.',
                'example' => 1,
            ],
            'date' => [
                'description' => 'Fecha de emisión del documento, en formato `AAAA-MM-DD`.',
                'example' => '2025-01-01',
            ],
            'total' => [
                'description' => 'Monto total del documento.',
                'example' => 1000,
            ],
            'recipient' => [
                'description' => 'RUT del receptor del documento, con guión y dígito verificador.',
                'example' => '23456789-6',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Resultado de la validación. `status` es uno de los estados de arriba, `description` su explicación y `received` indica si el SII recibió el documento. Los datos del documento (`document`, `number`, `date`, `total`, `recipient`) repiten los consultados; `date` viene en formato `DDMMAAAA`.
                    MD,
                'example' => [
                    'query_number' => null,
                    'query_datetime' => null,
                    'company' => '76192083-9',
                    'document' => 33,
                    'number' => 1,
                    'date' => '01012025',
                    'total' => 1000,
                    'recipient' => '23456789-6',
                    'status' => 'DOK',
                    'received' => true,
                    'description' => 'Documento recibido por el SII. Datos coinciden con los registrados.',
                ],
            ],
        ],
    )]
    public function validateDocument(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient
    ): ValidateDocumentResponse {
        return $this->validateDocumentJob->validate(
            $request,
            $company,
            $document,
            $number,
            $date,
            $total,
            $recipient
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate_document_signature',
        summary: 'Valida un documento aceptado por el SII y su firma',
        description: <<<'MD'
            Consulta al SII si un documento tributario electrónico fue aceptado, si los datos informados coinciden con los registrados y, además, si la firma electrónica del documento coincide con la que se envió al SII. Aplica a cualquier documento tributario electrónico, excepto las boletas.

            ## Cómo se usa

            Se envían los datos de conexión en `request`, los datos del documento y la firma del XML en `signature`. Si no se necesita comprobar la firma, se usa `billing.integration.sii_dte::validate_document`.

            ## Estados (`status`)

            | Valor | Significado |
            |---|---|
            | `DOK` | Documento recibido por el SII. Datos coinciden con los registrados. |
            | `DNK` | Documento recibido por el SII pero datos no coinciden con los registrados. |
            | `FAU` | Folio del documento no recibido por el SII. |
            | `FNA` | Folio no autorizado por el SII. |
            | `FAN` | Folio anulado antes de ser enviado al SII. |
            | `EMP` | Empresa no autorizada a emitir documentos tributarios electrónicos. |
            | `TMD` | Existe nota de débito que modifica el texto del documento. |
            | `TMC` | Existe nota de crédito que modifica el texto del documento. |
            | `MMD` | Existe nota de débito que modifica los montos del documento. |
            | `MMC` | Existe nota de crédito que modifica los montos del documento. |
            | `AND` | Existe nota de débito que anula el documento. |
            | `ANC` | Existe nota de crédito que anula el documento. |

            Detalle del servicio del SII: [Consulta Avanzada de Estado de DTE](https://www.sii.cl/factura_electronica/factura_mercado/OIFE2006_QueryEstDteAv_MDE.pdf).
            MD,
        parameters: [
            'request' => [
                'description' => self::REQUEST_DESCRIPTION,
                'example' => [
                        'certificate' => [
                            'certificate' => '',
                            'privateKey' => '',
                        ],
                        'options' => [
                            'environment' => 1,
                        ],
                    ],
            ],
            'company' => [
                'description' => 'RUT de la empresa emisora, con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
            'document' => [
                'description' => <<<'MD'
                        Código del tipo de documento tributario electrónico, por ejemplo `33` (factura electrónica). Los códigos válidos están en el catálogo: operación `system.repository.catalog::find_all` con `repository` igual a `libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface`.
                        MD,
                'example' => 33,
            ],
            'number' => [
                'description' => 'Folio del documento.',
                'example' => 1,
            ],
            'date' => [
                'description' => 'Fecha de emisión del documento, en formato `AAAA-MM-DD`.',
                'example' => '2025-01-01',
            ],
            'total' => [
                'description' => 'Monto total del documento.',
                'example' => 1000,
            ],
            'recipient' => [
                'description' => 'RUT del receptor del documento, con guión y dígito verificador.',
                'example' => '23456789-6',
            ],
            'signature' => [
                'description' => 'Valor de la firma del documento: el contenido de `Signature/SignatureValue` del XML del DTE.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Resultado de la validación. `status` es uno de los estados de arriba, `description` su explicación y `received` indica si el SII recibió el documento. `signature` trae la firma consultada. Los datos del documento repiten los consultados; `date` viene en formato `DDMMAAAA`.
                    MD,
                'example' => [
                    'query_number' => null,
                    'query_datetime' => null,
                    'company' => '76192083-9',
                    'document' => 33,
                    'number' => 1,
                    'date' => '01012025',
                    'total' => 1000,
                    'signature' => 'QdjpaqidVjAJXEjcVwY0CQvmXWAYmRODuL5T...',
                    'recipient' => '23456789-6',
                    'status' => 'DOK',
                    'received' => true,
                    'description' => 'Documento recibido por el SII. Datos coinciden con los registrados.',
                ],
            ],
        ],
    )]
    public function validateDocumentSignature(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient,
        string $signature
    ): ValidateDocumentSignatureResponse {
        return $this->validateDocumentSignatureJob->validate(
            $request,
            $company,
            $document,
            $number,
            $date,
            $total,
            $recipient,
            $signature
        );
    }
}
