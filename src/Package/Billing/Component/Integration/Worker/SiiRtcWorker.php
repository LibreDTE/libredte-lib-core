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
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRtcWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\CheckAecSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\GetAssignmentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\CheckAecSentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\GetAssignmentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\GetRelatedAssignmentStatusJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job\SendAecJob;

/**
 * Clase del worker del RTC del SII.
 *
 * Gestiona el envío del Archivo Electrónico de Cesión (AEC) al Registro de
 * Transferencias de Créditos (RTC) del SII y las consultas del estado de los
 * envíos y de las cesiones.
 */
#[Worker(name: 'sii_rtc', component: 'integration', package: 'billing')]
class SiiRtcWorker extends AbstractWorker implements SiiRtcWorkerInterface
{
    private const REQUEST_DESCRIPTION = <<<'MD'
        Datos de la conexión al SII.

        | Campo | Descripción |
        |---|---|
        | `certificate` | Obligatorio. Certificado digital con el que se autentica en el SII. Se acepta `certificate` y `privateKey` en PEM, `data` (archivo PKCS12 en base64) y `password`, o `cert` y `pkey`. |
        | `options.environment` | Ambiente del SII: `0` producción (valor por defecto) o `1` certificación (pruebas). |
        | `options.retries` | Cantidad máxima de intentos de cada llamada al SII, de `1` a `10`; por defecto `3`. Con muchos intentos la espera crece de forma exponencial. |
        | `options.timeout` | Segundos máximos de cada intento de una llamada al SII, de `10` a `60`; por defecto `20`. |
        | `options.max_duration` | Segundos máximos de una llamada al SII, sumando sus intentos y las pausas entre ellos, de `10` a `300`; por defecto `60`. La autenticación con el SII es una llamada aparte, con su propio tiempo. |
        | `options.verify_ssl` | Verifica el certificado SSL del SII; por defecto `true`. No se recomienda desactivarlo. |
        | `options.token` | Cómo se guarda el token de autenticación: `cache` (`memory` o `filesystem`; por defecto `memory`), `key` y `ttl` (segundos que se conserva; por defecto `2700`). |
        MD;

    public function __construct(
        private SendAecJob $sendAecJob,
        private CheckAecSentStatusJob $checkAecSentStatusJob,
        private GetAssignmentStatusJob $getAssignmentStatusJob,
        private GetRelatedAssignmentStatusJob $getRelatedAssignmentStatusJob,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'send_aec',
        summary: 'Envía un AEC al Registro de Transferencias de Créditos del SII',
        description: <<<'MD'
            Envía al Registro de Transferencias de Créditos (RTC) del SII un AEC (Archivo Electrónico de Cesión) y entrega el número de seguimiento (Track ID) del envío. Reproduce el envío manual de una cesión que el SII ofrece en su formulario web.

            ## Cómo se usa

            Se envían los datos de conexión en `request`, el XML del AEC en `doc`, el RUT del cedente en `company` y un correo de contacto del cedente en `emailNotif`, al que el SII notifica el resultado del procesamiento. Con el `track_id` se consulta el estado con `billing.integration.sii_rtc::check_aec_sent_status`. La operación falla si la empresa no está autorizada como emisor de documentos tributarios electrónicos.

            El AEC se construye con `billing.ownership_transfer.aec::build`.
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
                'description' => 'XML del AEC que se envía, codificado en base64.',
                'example' => '',
            ],
            'company' => [
                'description' => 'RUT del cedente (la empresa que cede el documento), con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
            'emailNotif' => [
                'description' => 'Correo electrónico de contacto del cedente, al que el SII notifica el resultado del procesamiento del AEC.',
                'example' => 'cedente@example.com',
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
    public function sendAec(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        string $emailNotif,
        ?int $retries = null
    ): SendAecResponse {
        return $this->sendAecJob->send(
            $request,
            $doc,
            $company,
            $emailNotif,
            $retries
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'check_aec_sent_status',
        summary: 'Consulta el estado de un AEC enviado al SII',
        description: <<<'MD'
            Obtiene el estado actualizado del envío de un AEC al Registro de Transferencias de Créditos (RTC) del SII. El estado puede no ser el final: en ese caso se debe repetir la consulta más adelante.

            ## Cómo se usa

            Se envían los datos de conexión en `request` y el `trackId` que entregó `billing.integration.sii_rtc::send_aec`. No hace falta indicar la empresa: el token de la solicitud identifica al contribuyente.

            ## Estados (`status`)

            | Valor | Significado |
            |---|---|
            | `UPL` | Envío en proceso de upload |
            | `RCP` | Envío recibido |
            | `RSC` | Rechazado por error en el esquema |
            | `SOK` | Esquema validado |
            | `RFS` | Rechazado por firma del sobre |
            | `FSO` | Firma del sobre validada |
            | `RCR` | Error en la carátula |
            | `COK` | Carátula OK |
            | `VDC` | Validación de documento |
            | `RDC` | Documento inválido |
            | `VCS` | Validación de cesiones |
            | `RCS` | Cesión inválida |
            | `EOK` | Envío aceptado |
            | `EAN` | Envío anulado |

            Son estados de error `RSC`, `RFS`, `RCR`, `RDC` y `RCS`. El estado es final cuando es de error, o es `EOK` o `EAN`: el SII ya no procesará más el envío. Cuando el SII no encuentra información del envío, `status` trae un código de error propio del SII.

            Detalle del servicio del SII: [Consulta Estado Envío de AEC al Registro](https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf).
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
                'description' => 'Número de seguimiento (Track ID) que asignó el SII al envío del AEC.',
                'example' => 1234567890,
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Estado del envío. `status` es uno de los estados de arriba (o un código de error del SII), `description` su explicación, `error` indica si es un error y `final` si es un estado final. El ejemplo muestra la respuesta cuando el SII no encuentra información del envío.
                    MD,
                'example' => [
                    'track_id' => 1234567890,
                    'status' => '2',
                    'description' => 'Error: no se encontró información.',
                    'error' => true,
                    'final' => false,
                ],
            ],
        ],
    )]
    public function checkAecSentStatus(
        SiiRequestInterface $request,
        int $trackId
    ): CheckAecSentStatusResponse {
        return $this->checkAecSentStatusJob->check($request, $trackId);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'get_assignment_status',
        summary: 'Consulta el estado de la cesión de un documento',
        description: <<<'MD'
            Obtiene el estado de la cesión de un documento en el Registro de Transferencias de Créditos (RTC) del SII.

            ## Cómo se usa

            Se envían los datos de conexión en `request` y el emisor, el tipo (`33` o `34`) y el folio del documento cedido. Sin la clave de acceso (`assignmentId`), la respuesta solo informa si el documento está cedido. Con ella, entrega además el tenedor, la fecha de la última anotación y la cobranza.

            Para consultar el detalle de la cesión como empresa vinculada se usa `billing.integration.sii_rtc::get_related_assignment_status`.

            Detalle del servicio del SII: [Consulta de Cesiones](https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf).
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
                'description' => 'RUT del emisor del documento cedido, con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
            'document' => [
                'description' => 'Código del tipo de documento tributario electrónico: `33` (factura electrónica) o `34` (factura no afecta o exenta electrónica).',
                'example' => 33,
            ],
            'number' => [
                'description' => 'Folio del documento.',
                'example' => 1234,
            ],
            'assignmentId' => [
                'description' => 'Clave de acceso a la información de la cesión. Opcional: sin ella la respuesta solo informa si el documento está cedido.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Estado de la cesión. `assigned` indica si el documento está cedido. `status` y `description` son los que entrega el SII. Con la clave de acceso a la cesión, la respuesta trae además el tenedor (`holder`), la fecha de la última anotación (`last_annotation`), el RUT y la dirección de cobranza (`collection_rut` y `collection_address`) y la declaración jurada (`sworn_declaration`); sin ella, esos campos vienen en `null`. El ejemplo muestra la respuesta cuando el SII no tiene información del documento.
                    MD,
                'example' => [
                    'assigned' => false,
                    'status' => '2',
                    'description' => 'Error: no se encontró información.',
                    'holder' => null,
                    'last_annotation' => null,
                    'collection_rut' => null,
                    'collection_address' => null,
                    'sworn_declaration' => null,
                ],
            ],
        ],
    )]
    public function getAssignmentStatus(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        ?string $assignmentId = null
    ): GetAssignmentStatusResponse {
        return $this->getAssignmentStatusJob->get(
            $request,
            $company,
            $document,
            $number,
            $assignmentId
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'get_related_assignment_status',
        summary: 'Consulta la cesión de un documento como empresa vinculada',
        description: <<<'MD'
            Obtiene el detalle de la cesión de un documento en el Registro de Transferencias de Créditos (RTC) del SII para una empresa vinculada con ella, ya sea como tenedor o como receptor del documento.

            ## Cómo se usa

            Se envían los datos de conexión en `request`, el emisor, el tipo (`33` o `34`) y el folio del documento cedido, y el RUT de la empresa vinculada en `relatedCompany`.

            Detalle del servicio del SII: [Consulta de Cesiones](https://www.sii.cl/factura_electronica/ws_consulta_estado_aec.pdf).
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
                'description' => 'RUT del emisor del documento cedido, con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
            'document' => [
                'description' => 'Código del tipo de documento tributario electrónico: `33` (factura electrónica) o `34` (factura no afecta o exenta electrónica).',
                'example' => 33,
            ],
            'number' => [
                'description' => 'Folio del documento.',
                'example' => 1234,
            ],
            'relatedCompany' => [
                'description' => 'RUT de la empresa vinculada con la cesión (tenedor o receptor), con guión y dígito verificador.',
                'example' => '76192083-9',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Estado de la cesión. `assigned` indica si el documento está cedido. `status` y `description` son los que entrega el SII. La respuesta trae además el tenedor (`holder`), la fecha de la última anotación (`last_annotation`), el RUT y la dirección de cobranza (`collection_rut` y `collection_address`) y la declaración jurada (`sworn_declaration`) cuando el SII los informa; si no, vienen en `null`. El ejemplo muestra la respuesta cuando el SII no tiene información del documento.
                    MD,
                'example' => [
                    'assigned' => false,
                    'status' => '2',
                    'description' => 'Error: no se encontró información.',
                    'holder' => null,
                    'last_annotation' => null,
                    'collection_rut' => null,
                    'collection_address' => null,
                    'sworn_declaration' => null,
                ],
            ],
        ],
    )]
    public function getRelatedAssignmentStatus(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $relatedCompany
    ): GetAssignmentStatusResponse {
        return $this->getRelatedAssignmentStatusJob->get(
            $request,
            $company,
            $document,
            $number,
            $relatedCompany
        );
    }
}
