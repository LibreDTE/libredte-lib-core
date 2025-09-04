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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\ApiResource;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;
use Throwable;

/**
 * Clase para los renderizadores.
 */
#[Worker(name: 'renderer', component: 'document', package: 'billing')]
class RendererWorker extends AbstractWorker implements RendererWorkerInterface
{
    use StrategiesAwareTrait;

    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        iterable $strategies = []
    ) {
        $this->setStrategies($strategies);
    }

    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        '__allowUndefinedKeys' => true,
        'strategy' => [
            'types' => 'string',
            'default' => 'template.estandar',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    #[ApiResource(
        parametersExample: [
            'bag' => [
                'xmlDocument' => '',
                'options' => [
                    'renderer' => [
                        'format' => 'pdf',
                    ],
                ],
            ],
        ]
    )]
    public function render(DocumentBagInterface $bag): string
    {
        $options = $this->resolveOptions($bag->getRendererOptions());
        $strategy = $this->getStrategy($options->get('strategy'));

        assert($strategy instanceof RendererStrategyInterface);

        $bag = $this->documentBagManager->normalize($bag, all: true);

        try {
            $renderedData = $strategy->render($bag);
        } catch (Throwable $e) {
            throw new RendererException(
                message: $e->getMessage(),
                documentBag: $bag,
                previous: $e
            );
        }

        return $renderedData;
    }
}
