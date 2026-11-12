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
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use Throwable;

/**
 * Worker "billing.exchange.receiver".
 */
#[Worker(name: 'receiver', component: 'exchange', package: 'billing')]
class ReceiverWorker extends AbstractExchangeWorker implements ReceiverWorkerInterface
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
            'default' => 'email.imap',
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
        name: 'receive',
        summary: 'Recibe documentos desde un buzón de correo electrónico',
        description: <<<'MD'
            Recibe los documentos que llegaron a un buzón de correo electrónico (IMAP). Cada correo que trae archivos XML adjuntos se entrega como un sobre, con sus documentos. Un correo sin adjuntos XML no se considera un sobre y se ignora.

            ## Cómo se usa

            Se envía `bag` con las opciones del transporte (la cuenta de correo y cómo buscar en ella). La respuesta trae los sobres encontrados en `envelopes`.

            ## Qué correos se leen

            Por defecto se leen los correos no leídos de los últimos 7 días. Con `options.transport.search` se cambia:

            | Opción | Descripción |
            |---|---|
            | `criteria` | Criterio de búsqueda IMAP, por ejemplo `UNSEEN SINCE 2024-01-15`. Si se indica, `daysAgo` no se usa. |
            | `daysAgo` | Cantidad de días hacia atrás que se buscan. Por defecto `7`. Solo se usa si no se indica `criteria`. |
            | `markAsSeen` | Si es `true`, los correos procesados se marcan como leídos. **Por defecto es `true`**: una llamada normal modifica el buzón; para solo consultar sin alterarlo se envía `false`. |

            De cada correo se toma el cuerpo (texto o HTML) y los adjuntos XML. Los demás adjuntos se ignoran.

            ## Resultado

            La respuesta repite las opciones enviadas, incluidas las credenciales del transporte.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Opciones de la recepción.

                    | Campo | Descripción |
                    |---|---|
                    | `options.strategy` | Forma de recepción; por ahora `email.imap`, que es el valor por defecto. |
                    | `options.transport` | Cuenta de correo desde la que se recibe. Obligatorios `username` y `password`. Opcionales: `host` (por defecto `imap.gmail.com`), `port` (por defecto `993`), `encryption` (`ssl` por defecto; `null` para ninguna), `mailbox` (carpeta; por defecto `INBOX`), `verify_peer` (verifica el certificado del servidor; por defecto `true`), `timeout` y `search` (qué correos se leen, ver más arriba). |
                    MD,
                'example' => [
                    'options' => [
                        'strategy' => 'email.imap',
                        'transport' => [
                            'username' => '',
                            'password' => '',
                        ],
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Sobres recibidos. `envelopes` trae un sobre por correo, con `sender` y `receiver` (las direcciones de correo del remitente y el destinatario), `document_type` y `process`, `business_message_id` (identificador del mensaje en el buzón), `creation_date_and_time` y `documents`. Cada documento trae sus archivos en `attachments`, con `data` en base64, `name`, `type` y `size`. `results` trae el resultado de cada sobre; en la recepción no tiene estados. Si no hay correos que cumplan, `envelopes` y `results` vienen vacíos.
                    MD,
                'example' => [
                    'options' => [
                        'strategy' => 'email.imap',
                        'transport' => [
                            'username' => 'receptor@example.com',
                            'password' => 'clave',
                            'host' => 'imap.example.com',
                            'port' => 993,
                            'encryption' => 'ssl',
                            'verify_peer' => true,
                            'search' => [],
                        ],
                    ],
                    'envelopes' => [
                        [
                            'sender' => [
                                'identifier' => [
                                    'id' => 'EMAIL:emisor@example.com',
                                    'value' => 'emisor@example.com',
                                    'scheme_id' => 'EMAIL',
                                    'scheme_name' => 'Correo electrónico',
                                    'authority' => 'INTERNET',
                                ],
                                'endpoints' => [],
                            ],
                            'receiver' => [
                                'identifier' => [
                                    'id' => 'EMAIL:receptor@example.com',
                                    'value' => 'receptor@example.com',
                                    'scheme_id' => 'EMAIL',
                                    'scheme_name' => 'Correo electrónico',
                                    'authority' => 'INTERNET',
                                ],
                                'endpoints' => [],
                            ],
                            'document_type' => 'urn:fdc:libredte.cl:2025:doc:b2b',
                            'process' => 'urn:fdc:libredte.cl:2025:poacc:billing',
                            'business_message_id' => '1',
                            'original_business_message_id' => null,
                            'creation_date_and_time' => '2024-01-15T10:00:00-03:00',
                            'documents' => [
                                [
                                    'id' => '5b7931f5-1365-4a75-aea6-8de532b07d54',
                                    'type' => 'urn:fdc:libredte.cl:2025:doc:b2b',
                                    'content' => '',
                                    'attachments' => [
                                        [
                                            'data' => 'PEVudmlvRFRFPkZpeHR1cmUgZGUgcHJ1ZWJh...',
                                            'name' => 'envio.xml',
                                            'type' => 'application/xml',
                                            'size' => 71,
                                        ],
                                    ],
                                    'metadata' => [
                                        'id' => '5b7931f5-1365-4a75-aea6-8de532b07d54',
                                    ],
                                ],
                            ],
                            'metadata' => [],
                        ],
                    ],
                    'results' => [
                        [
                            'envelope' => '1',
                            'statuses' => [],
                            'metadata' => [],
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function receive(ExchangeBagInterface $bag): ExchangeBagInterface
    {
        $options = $this->resolveOptions($bag->getOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

        assert($strategy instanceof ReceiverStrategyInterface);

        try {
            $strategy->receive($bag);
        } catch (Throwable $e) {
            throw new ExchangeException(
                message: [
                    'Could not receive the exchange: {detail}',
                    'detail' => $e,
                ],
                previous: $e
            );
        }

        return $bag;
    }
}
