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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use Throwable;

/**
 * Worker "billing.book.loader".
 *
 * Normaliza los datos de entrada de cualquier tipo de libro tributario desde
 * cualquier formato de origen (array, CSV, XML, etc.).
 *
 * Construye el nombre de estrategia como `{tipo}.{formato}` donde:
 *   - `tipo` proviene de `BookBagInterface::getTipo()`.
 *   - `formato` proviene de la opción `format` del bag (por defecto 'array').
 *
 * Ejemplos de estrategias disponibles:
 *   - `libro_ventas.array`, `libro_ventas.csv`, `libro_ventas.xml`
 *   - `libro_compras.array`, `libro_compras.csv`
 *   - `libro_boletas.array`
 *   - `libro_guias.array`
 *   - `resumen_ventas_diarias.array`
 */
#[Worker(name: 'loader', component: 'book', package: 'billing')]
class LoaderWorker extends AbstractWorker implements LoaderWorkerInterface
{
    use StrategiesAwareTrait;

    /**
     * Esquema de las opciones del worker.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $optionsSchema = [
        'format' => [
            'types' => 'string',
            'default' => 'array',
        ],
    ];

    public function __construct(iterable $strategies = [])
    {
        $this->setStrategies($strategies);
    }

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
        parameters: [
            'bag' => [
                'example' => [
                    'tipo' => TipoLibro::VENTAS,
                    'caratula' => [
                        'RutEmisorLibro' => '76192083-9',
                        'PeriodoTributario' => '2024-01',
                    ],
                    'detalle' => [
                        [
                            'TpoDoc' => 33,
                            'NroDoc' => 1,
                            'TasaImp' => 19,
                            'FchDoc' => '2024-01-10',
                            'RUTDoc' => '12345678-9',
                            'RznSoc' => 'Empresa Compradora SpA',
                            'MntNeto' => 100000,
                            'MntIVA' => 19000,
                            'MntTotal' => 119000,
                        ],
                    ],
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'emisor' => [
                        'rut' => '76192083-9',
                        'razon_social' => 'SASCO SpA',
                    ],
                ],
            ],
        ],
    )]
    public function load(BookBagInterface $bag): BookBagInterface
    {
        $options = $this->resolveOptions($bag->getLoaderOptions());
        $format = $options->getReader()->requireString('format');
        $strategyName = $bag->getTipo()->value . '.' . $format;
        $strategy = $this->getStrategy($strategyName);

        assert($strategy instanceof LoaderStrategyInterface);

        try {
            return $strategy->load($bag);
        } catch (Throwable $e) {
            throw new BookException(
                message: [
                    'Could not load the book "{type}" from format "{format}": {detail}',
                    'type' => $bag->getTipo()->value,
                    'format' => $format,
                    'detail' => $e,
                ],
                previous: $e
            );
        }
    }
}
