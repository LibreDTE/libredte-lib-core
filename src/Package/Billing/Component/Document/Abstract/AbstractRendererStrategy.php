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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Renderer\Contract\RendererInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererStrategyInterface;

/**
 * Clase abstracta (base) para las estrategias de renderizado de documentos
 * tributarios utilizando plantillas.
 */
abstract class AbstractRendererStrategy extends AbstractStrategy implements RendererStrategyInterface
{
    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        '__allowUndefinedKeys' => true,
    ];

    public function __construct(
        private RendererInterface $renderer
    ) {
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
    public function render(DocumentBagInterface $bag): string
    {
        [$data, $options] = $this->createDataAndOptions($bag);

        return $this->renderer->render(
            $options['filepath'],
            $data,
            $options
        );
    }

    /**
     * Crea los datos que se pasarán a la plantilla que se renderizará.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos del documento a
     * renderizar.
     * @return array Datos que se pasarán a la plantilla al renderizar.
     */
    protected function createDataAndOptions(DocumentBagInterface $bag): array
    {
        // Preparar datos que se usarán para renderizar.
        $data = $bag->toArray();

        // Preparar opciones que se usarán para renderizar.
        $options = $this->resolveOptions($bag->getRendererOptions());
        $options = [
            'template' => $options->getReader()->requireString('template'),
            'filepath' => null,
            'format' => $options->getReader()->requireString('format'),
            'config' => [
                'html' => $options->getReader()->getArray('html') ?? [],
                'pdf' => $options->getReader()->getArray('pdf') ?? [],
            ],
        ];

        // Asignar la ubicación de la plantilla.
        if ($options['template'][0] === '/') {
            $options['filepath'] = $options['template'];
            $options['template'] = basename($options['template']);
        } else {
            $base = 'billing/document/renderer/';
            $options['filepath'] = $base . $options['template'];
        }

        // Entregar los datos que se pasarán a la plantilla y las opciones.
        return [$data, $options];
    }
}
