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
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRcvWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\CheckDocumentAssignabilityResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\GetDocumentSiiReceptionDateResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\ListDocumentEventsResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\SubmitDocumentAcceptanceResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\CheckDocumentAssignabilityJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\GetDocumentSiiReceptionDateJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\ListDocumentEventsJob;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job\SubmitDocumentAcceptanceJob;

/**
 * Clase del worker del Registro de Compra y Venta (RCV) del SII.
 */
#[Worker(name: 'sii_rcv', component: 'integration', package: 'billing')]
class SiiRcvWorker extends AbstractWorker implements SiiRcvWorkerInterface
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
        private SubmitDocumentAcceptanceJob $submitDocumentAcceptanceJob,
        private ListDocumentEventsJob $listDocumentEventsJob,
        private CheckDocumentAssignabilityJob $checkDocumentAssignabilityJob,
        private GetDocumentSiiReceptionDateJob $getDocumentSiiReceptionDateJob
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'submit_document_acceptance',
        summary: 'Registra una aceptación o reclamo de un documento en el SII',
        description: <<<'MD'
            Registra en el Registro de Compras y Ventas (RCV) del SII una aceptación o un reclamo de un documento tributario electrónico recibido.

            ## Cómo se usa

            Se envían los datos de conexión en `request`, el emisor del documento, su tipo y folio, y la acción a registrar en `action`.

            ## Acciones (`action`)

            | Valor | Acción |
            |---|---|
            | `ERM` | Entrega real de mercaderías o servicios (Ley 19.983) |
            | `ACD` | Acepta el contenido del documento |
            | `RCD` | Reclamo al contenido del documento |
            | `RFP` | Reclamo por falta parcial de mercaderías |
            | `RFT` | Reclamo por falta total de mercaderías |

            Los textos y códigos de la respuesta son los que entrega el SII.
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
                'description' => 'RUT del emisor del documento, con guión y dígito verificador.',
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
            'action' => [
                'description' => 'Acción que se registra: `ERM`, `ACD`, `RCD`, `RFP` o `RFT` (ver más arriba).',
                'example' => 'ACD',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Resultado del registro de la acción: `codigo` y `glosa` son los que entrega el SII. El ejemplo muestra la respuesta cuando el SII no tiene registros para el documento indicado.
                    MD,
                'example' => [
                    'codigo' => 9,
                    'glosa' => 'No existen registros de acuerdo a los parametros...',
                ],
            ],
        ],
    )]
    public function submitDocumentAcceptance(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $action
    ): SubmitDocumentAcceptanceResponse {
        return $this->submitDocumentAcceptanceJob->submit(
            $request,
            $company,
            $document,
            $number,
            $action
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'list_document_events',
        summary: 'Lista los eventos de un documento en el SII',
        description: <<<'MD'
            Entrega el historial de eventos de un documento tributario electrónico en el Registro de Compras y Ventas (RCV) del SII: aceptaciones, reclamos y otras acciones registradas.

            ## Cómo se usa

            Se envían los datos de conexión en `request` y el emisor, el tipo y el folio del documento. Si el documento no tiene eventos, la respuesta es una lista vacía.

            Los textos y códigos de la respuesta son los que entrega el SII.
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
                'description' => 'RUT del emisor del documento, con guión y dígito verificador.',
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
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Lista de eventos del documento, en el orden que entrega el SII. Cada evento trae su `codigo` y `glosa`, el `responsable` (RUT con guión y dígito verificador) y la `fecha` en formato `AAAA-MM-DD HH:MM:SS`. Vacía si no hay eventos.
                    MD,
                'example' => [
                    [
                        'codigo' => 'ACD',
                        'glosa' => 'Acepta Contenido del Documento',
                        'responsable' => '76192083-9',
                        'fecha' => '2024-01-15 10:00:00',
                    ],
                ],
            ],
        ],
    )]
    public function listDocumentEvents(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): ListDocumentEventsResponse {
        return $this->listDocumentEventsJob->list(
            $request,
            $company,
            $document,
            $number
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'check_document_assignability',
        summary: 'Consulta si un documento se puede ceder',
        description: <<<'MD'
            Consulta en el SII si un documento tributario electrónico puede ser cedido (factoring o cesión de crédito con un AEC).

            ## Cómo se usa

            Se envían los datos de conexión en `request` y el emisor, el tipo y el folio del documento. Si el documento se puede ceder, se construye el archivo de cesión con `billing.ownership_transfer.aec::build`.

            Los textos y códigos de la respuesta son los que entrega el SII.
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
                'description' => 'RUT del emisor del documento, con guión y dígito verificador.',
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
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Resultado de la consulta: `codigo` y `glosa` son los que entrega el SII e indican si el documento se puede ceder. El ejemplo muestra la respuesta cuando el SII no ha recibido el documento.
                    MD,
                'example' => [
                    'codigo' => 18,
                    'glosa' => 'Documento no ha sido recibido',
                ],
            ],
        ],
    )]
    public function checkDocumentAssignability(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): CheckDocumentAssignabilityResponse {
        return $this->checkDocumentAssignabilityJob->check(
            $request,
            $company,
            $document,
            $number
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'get_document_sii_reception_date',
        summary: 'Consulta la fecha en que el SII recibió un documento',
        description: <<<'MD'
            Consulta la fecha y hora en que el SII recibió un documento tributario electrónico.

            ## Cómo se usa

            Se envían los datos de conexión en `request` y el emisor, el tipo y el folio del documento. Si el SII no tiene la fecha de recepción del documento, la operación falla.
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
                'description' => 'RUT del emisor del documento, con guión y dígito verificador.',
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
        ],
        results: [
            'success' => [
                'description' => 'Fecha de recepción del documento en el SII, en formato `AAAA-MM-DD HH:MM:SS`.',
                'example' => [
                    'fecha_recepcion_sii' => '2024-01-15 10:00:00',
                ],
            ],
        ],
    )]
    public function getDocumentSiiReceptionDate(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): GetDocumentSiiReceptionDateResponse {
        return $this->getDocumentSiiReceptionDateJob->get(
            $request,
            $company,
            $document,
            $number
        );
    }
}
