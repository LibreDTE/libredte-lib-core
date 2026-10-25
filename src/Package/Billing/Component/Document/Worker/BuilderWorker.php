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

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;

/**
 * Clase para los constructores de documentos.
 */
#[Worker(name: 'builder', component: 'document', package: 'billing')]
class BuilderWorker extends AbstractWorker implements BuilderWorkerInterface
{
    use StrategiesAwareTrait;

    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        iterable $jobs = [],
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        $this->setJobs($jobs);
        $this->setHandlers($handlers);
        $this->setStrategies($strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function create(DocumentBagInterface $bag): DocumentBagInterface
    {
        // Buscar la estrategia para crear el documento tributario.
        $strategy = $this->getStrategy(
            $bag->getTipoDocumento()->getAlias()
        );
        assert($strategy instanceof BuilderStrategyInterface);

        // Construir el documento usando la estrategia.
        $document = $strategy->create($bag->getXmlDocument());
        $bag->setDocument($document);

        // Entregar la bolsa con el DTE construído.
        return $bag;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'bag' => [
                'example' => [
                    'parsedData' => [
                        'Encabezado' => [
                            'IdDoc' => [
                                'TipoDTE' => 33,
                                'Folio' => 1,
                            ],
                            'Emisor' => [
                                'RUTEmisor' => '12345678-5',
                                'RznSoc' => 'Empresa S.A.',
                                'GiroEmis' => 'Giro de la empresa',
                                'DirOrigen' => 'Santiago',
                                'CmnaOrigen' => 'Santiago',
                            ],
                            'Receptor' => [
                                'RUTRecep' => '23456789-6',
                                'RznSocRecep' => 'Empresa S.A.',
                                'GiroRecep' => 'Giro de la empresa',
                                'DirRecep' => 'Santiago',
                                'CmnaRecep' => 'Santiago',
                            ],
                        ],
                        'Detalle' => [
                            [
                                'NmbItem' => 'Producto A',
                                'QtyItem' => 1,
                                'PrcItem' => 1000,
                            ],
                        ],
                    ],
                    'caf' => '',
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                ],
            ],
        ],
    )]
    public function build(DocumentBagInterface $bag): DocumentBagInterface
    {
        // Normalizar la bolsa con los datos del documento.
        // Acá no se puede normalizar todo, solo lo necesario. El resto de la
        // normalización (todo) se debe hacer al final de este método antes de
        // retornar. Esto es así porque para hacer la normalización completa se
        // requiere que el DTE esté creado.
        $bag = $this->documentBagManager->normalize($bag);

        // Buscar la estrategia para construir el documento tributario.
        $strategy = $this->getStrategy($bag->getAlias());
        assert($strategy instanceof BuilderStrategyInterface);

        // Construir el documento usando la estrategia.
        $strategy->build($bag);

        // Normalizar la bolsa con los datos actualizados del documento.
        $bag = $this->documentBagManager->normalize($bag, all: true);

        // Entregar la bolsa con el DTE construído.
        return $bag;
    }
}
