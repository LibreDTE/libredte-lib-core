<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
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
    public function load(BookBagInterface $bag): BookBagInterface
    {
        $options = $this->resolveOptions($bag->getLoaderOptions());
        $format = $options->get('format');
        $strategyName = $bag->getTipo()->value . '.' . $format;
        $strategy = $this->getStrategy($strategyName);

        assert($strategy instanceof LoaderStrategyInterface);

        try {
            return $strategy->load($bag);
        } catch (Throwable $e) {
            throw new BookException(
                message: sprintf(
                    'No fue posible cargar el libro "%s" desde formato "%s": %s',
                    $bag->getTipo()->value,
                    $format,
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }
}
