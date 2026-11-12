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

use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Trait\HandlersAwareTrait;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use Throwable;

/**
 * Worker "billing.exchange.sender".
 */
#[Worker(name: 'sender', component: 'exchange', package: 'billing')]
class SenderWorker extends AbstractExchangeWorker implements SenderWorkerInterface
{
    use HandlersAwareTrait;
    use StrategiesAwareTrait;

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param iterable<array-key, HandlerInterface> $handlers
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        $this->setHandlers($handlers);
        $this->setStrategies($strategies);
    }

    /**
     * Esquema de las opciones.
     *
     * @var array<string, array<string, mixed>|bool>
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'default' => 'email.smtp',
        ],
        'transport' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'send',
        summary: 'Envía documentos a un receptor por correo electrónico',
        description: <<<'MD'
            Envía uno o más sobres de documentos a sus receptores por correo electrónico (SMTP). Cada sobre se envía como un correo, con sus documentos y archivos adjuntos. Sirve para el intercambio de documentos tributarios entre empresas.

            ## Cómo se usa

            Se envía `bag` con las opciones del transporte (la cuenta de correo desde la que se envía) y los sobres. Para cada sobre se indica quién envía, quién recibe, qué tipo de documento y proceso es, y los documentos con sus archivos.

            El correo sale de la cuenta indicada en `options.transport`, con el remitente y los destinatarios que tengan `scheme_id` igual a `EMAIL` en sus `endpoints`. Las opciones de transporte son comunes a todos los sobres de la llamada; para enviar con cuentas distintas se hace una llamada por cuenta.

            En cada sobre se pueden indicar, dentro de `metadata`: `subject` (asunto; por defecto "Envío de documentos electrónicos de" más el identificador del remitente), `text` y `html` (cuerpo del correo en texto y en HTML) y `to`, `cc` y `bcc` (listas de correos adicionales). Los XML u otros archivos se envían como `attachments` de cada documento.

            ## Tipos de documento (`document_type`)

            | Valor | Documento |
            |---|---|
            | `urn:fdc:libredte.cl:2025:doc:b2b` | Intercambio entre empresas. Es el valor por defecto. |
            | `urn:fdc:libredte.cl:2025:doc:b2c` | Intercambio con un consumidor final. |
            | `urn:fdc:libredte.cl:2025:doc:invoice` | Factura |
            | `urn:fdc:libredte.cl:2025:doc:credit_note` | Nota de crédito |
            | `urn:fdc:libredte.cl:2025:doc:debit_note` | Nota de débito |
            | `urn:fdc:libredte.cl:2025:doc:dispatch_advice` | Guía de despacho |
            | `urn:fdc:libredte.cl:2025:doc:receipt_advice` | Acuse de recibo |
            | `urn:fdc:libredte.cl:2025:doc:application_response` | Respuesta de aplicación (aceptación o rechazo de otro documento) |

            ## Proceso (`process`)

            El valor por defecto es `urn:fdc:libredte.cl:2025:poacc:billing` (facturación), que es el que corresponde al intercambio de documentos tributarios.

            ## Identificadores (`scheme_id`)

            `CL-RUT` identifica a una empresa por su RUT, y `EMAIL` identifica una dirección de correo electrónico.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del envío.

                    | Campo | Descripción |
                    |---|---|
                    | `options.strategy` | Forma de envío; por ahora `email.smtp`, que es el valor por defecto. |
                    | `options.transport` | Cuenta de correo desde la que se envía. Obligatorios `username` y `password`. Opcionales: `host` (por defecto `smtp.gmail.com`), `port` (por defecto `465`), `encryption` (`ssl` por defecto; `null` para ninguna), `verify_peer` (verifica el certificado del servidor; por defecto `true`) y `timeout`. |
                    | `envelopes` | Lista de sobres a enviar. |

                    Cada sobre de `envelopes` tiene:

                    | Campo | Descripción |
                    |---|---|
                    | `sender` | Quien envía: `identifier` (`value` y `scheme_id`, por ejemplo el RUT con `CL-RUT`) y `endpoints`, la lista de direcciones (`value` y `scheme_id`: `EMAIL`). Se usa la dirección de correo del remitente. |
                    | `receiver` | Quien recibe, con la misma estructura que `sender`. Se envía el correo a sus direcciones `EMAIL`. |
                    | `document_type` | Tipo de documento (ver más arriba). Por defecto `urn:fdc:libredte.cl:2025:doc:b2b`. |
                    | `process` | Proceso (ver más arriba). Por defecto `urn:fdc:libredte.cl:2025:poacc:billing`. |
                    | `business_message_id` | Identificador del envío, para distinguirlo; es el que se entrega en la respuesta. |
                    | `documents` | Lista de documentos. Cada uno tiene `type` y `attachments`, la lista de archivos con `data` (el contenido en base64), `name` y `type` (tipo MIME, por ejemplo `application/xml`). Un `content` (texto en base64) se adjunta como un archivo de texto. |
                    | `metadata` | Datos del correo: `subject`, `text`, `html`, `to`, `cc` y `bcc`. |
                    MD,
                'example' => [
                    'options' => [
                        'strategy' => 'email.smtp',
                        'transport' => [
                            'username' => '',
                            'password' => '',
                        ],
                    ],
                    'envelopes' => [
                        [
                            'sender' => [
                                'identifier' => [
                                    'value' => '76192083-9',
                                    'scheme_id' => 'CL-RUT',
                                ],
                                'endpoints' => [
                                    [
                                        'value' => 'emisor@example.com',
                                        'scheme_id' => 'EMAIL',
                                    ],
                                ],
                            ],
                            'receiver' => [
                                'identifier' => [
                                    'value' => '66666666-6',
                                    'scheme_id' => 'CL-RUT',
                                ],
                                'endpoints' => [
                                    [
                                        'value' => 'cliente@example.com',
                                        'scheme_id' => 'EMAIL',
                                    ],
                                ],
                            ],
                            'document_type' => 'urn:fdc:libredte.cl:2025:doc:invoice',
                            'process' => 'urn:fdc:libredte.cl:2025:poacc:billing',
                            'business_message_id' => 'envio-001',
                            'documents' => [
                                [
                                    'type' => 'urn:fdc:libredte.cl:2025:doc:invoice',
                                    'attachments' => [
                                        [
                                            'data' => '',
                                            'name' => 'envio.xml',
                                            'type' => 'application/xml',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => 'Lista con el resultado de cada sobre enviado. `envelope` es el `business_message_id` del sobre y `statuses` trae un elemento por cada forma de envío usada: `strategy` es la forma (`email.smtp`) y `error` es `null` si el envío fue exitoso o trae el motivo si falló.',
                'example' => [
                    [
                        'envelope' => 'envio-001',
                        'statuses' => [
                            [
                                'strategy' => 'email.smtp',
                                'error' => null,
                                'metadata' => [],
                            ],
                        ],
                        'metadata' => [],
                    ],
                ],
            ],
        ],
    )]
    public function send(ExchangeBagInterface $bag): array
    {
        $options = $this->resolveOptions($bag->getOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

        $strategy = ValueCaster::requireObject($strategy, SenderStrategyInterface::class);

        try {
            $results = $strategy->send($bag);
        } catch (Throwable $e) {
            throw new ExchangeException(
                message: [
                    'Could not send the exchange: {detail}',
                    'detail' => $e,
                ],
                previous: $e
            );
        }

        return $results;
    }
}
