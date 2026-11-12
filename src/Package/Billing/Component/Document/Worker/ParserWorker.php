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

use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use Throwable;

/**
 * Clase para los parsers de datos de entrada de los documentos tributarios.
 */
#[Worker(name: 'parser', component: 'document', package: 'billing')]
class ParserWorker extends AbstractWorker implements ParserWorkerInterface
{
    use StrategiesAwareTrait;

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(
        iterable $strategies = []
    ) {
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
            'default' => 'default.json',
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
        name: 'parse',
        summary: 'Interpreta los datos de entrada de un documento',
        description: <<<'MD'
            Transforma los datos de entrada de un documento tributario (en JSON, XML, YAML o desde un formulario) en la estructura de datos del documento según el formato del SII, con campos como `Encabezado` y `Detalle`. No construye ni firma el documento; para eso se usa `billing.document.builder::build`.

            ## Cómo se usa

            Se envía `bag` con `inputData`. El formato de los datos se indica con `bag.options.parser.strategy`:

            | Valor | Formato de `inputData` |
            |---|---|
            | `default.json` | JSON con la estructura del documento. Es el valor por defecto. |
            | `default.xml` | XML con la estructura del documento. |
            | `default.yaml` | YAML con la estructura del documento. |
            | `form.estandar` | Datos del formulario de emisión estándar de LibreDTE, que se transforman a la estructura oficial del SII. |

            La respuesta trae los datos interpretados en `document_parsed`, que se pueden enviar como `parsedData` a `billing.document.builder::build`.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del documento.

                    | Campo | Descripción |
                    |---|---|
                    | `inputData` | Obligatorio. Los datos de entrada. Pueden enviarse como un objeto JSON, con la estructura del documento (solo con la estrategia `default.json`), o como un texto codificado en base64 con el contenido original (JSON, XML, YAML o los datos del formulario, según la estrategia). |
                    | `options` | Opciones. `options.parser.strategy` es la estrategia de lectura (ver más arriba); por defecto `default.json`. |
                    MD,
                'example' => [
                    'inputData' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NmbItem' => 'Servicio de prueba',
                                'QtyItem' => 1,
                                'PrcItem' => 10000,
                            ],
                        ],
                    ],
                    'options' => [
                        'parser' => [
                            'strategy' => 'default.json',
                        ],
                    ],
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => 'Los datos interpretados están en `document_parsed`. Los demás campos de la bolsa del documento vienen en `null` porque aún no se construye el documento.',
                'example' => [
                    'document' => null,
                    'document_parsed' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NmbItem' => 'Servicio de prueba',
                                'QtyItem' => 1,
                                'PrcItem' => 10000,
                            ],
                        ],
                    ],
                    'document_normalized' => null,
                    'document_extra' => null,
                    'document_stamp' => null,
                    'document_auth' => null,
                    'document_type' => null,
                    'document_id' => null,
                    'document_xml' => null,
                    'options' => [
                        'builder' => [],
                        'normalizer' => [],
                        'parser' => [],
                        'renderer' => [],
                        'sanitizer' => [],
                        'validator' => [],
                    ],
                    'certificate' => null,
                    'emisor' => null,
                    'receptor' => null,
                    'caf' => null,
                    'timbre' => null,
                ],
            ],
        ],
    )]
    public function parse(DocumentBagInterface $bag): DocumentBagInterface
    {
        $options = $this->resolveOptions($bag->getParserOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

        $strategy = ValueCaster::requireObject($strategy, ParserStrategyInterface::class);

        try {
            $parsedData = ArrayReader::fromArray(
                $strategy->parse($bag->requireInputData()),
                'parsed document'
            )->toMap();
        } catch (Throwable $e) {
            throw new ParserException(
                message: [
                    'Could not interpret the document: {detail}',
                    'detail' => $e,
                ],
                documentBag: $bag,
                previous: $e
            );
        }

        $bag->setParsedData($parsedData);

        return $bag;
    }
}
