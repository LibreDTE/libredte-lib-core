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

namespace libredte\lib\Core\Package\System\Component\Rendering\Support;

use libredte\lib\Core\Package\System\Component\Rendering\Contract\RenderedDocumentInterface;
use libredte\lib\Core\Package\System\Component\Rendering\Contract\RenderResultInterface;
use libredte\lib\Core\Package\System\Component\Rendering\Exception\RenderResultException;

/**
 * Clase para representar el resultado de un renderizado.
 */
class RenderResult implements RenderResultInterface
{
    /**
     * Archivos generados por el renderizado.
     *
     * @var RenderedDocumentInterface[]
     */
    private readonly array $renderings;

    /**
     * Archivos generados por el renderizado, indexados por su `label`.
     *
     * Solo incluye los que tienen `label` asignado (no es el caso de uso
     * normal, pero un `RenderedDocumentInterface` sin `label` sigue siendo
     * válido, solo no queda indexado para `hasRendering()`/`getRendering()`).
     *
     * @var array<string,RenderedDocumentInterface>
     */
    private readonly array $renderingsByLabel;

    /**
     * Constructor del resultado del renderizado.
     *
     * @param RenderedDocumentInterface ...$renderings Archivos generados
     * por el renderizado.
     */
    public function __construct(RenderedDocumentInterface ...$renderings)
    {
        $this->renderings = $renderings;

        $renderingsByLabel = [];
        foreach ($renderings as $rendering) {
            if ($rendering->getLabel() !== null) {
                $renderingsByLabel[$rendering->getLabel()] = $rendering;
            }
        }
        $this->renderingsByLabel = $renderingsByLabel;
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderings(): array
    {
        return $this->renderings;
    }

    /**
     * {@inheritDoc}
     */
    public function hasRendering(string $label): bool
    {
        return isset($this->renderingsByLabel[$label]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRendering(string $label): RenderedDocumentInterface
    {
        if (!isset($this->renderingsByLabel[$label])) {
            throw new RenderResultException(sprintf(
                'El renderizado no generó ningún archivo con la presentación "%s".',
                $label
            ));
        }

        return $this->renderingsByLabel[$label];
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        if (count($this->renderings) !== 1) {
            throw new RenderResultException(sprintf(
                'No es posible representar el resultado del renderizado como un solo string: contiene %d archivo(s), se esperaba exactamente 1.',
                count($this->renderings)
            ));
        }

        return $this->renderings[0]->getContent();
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'renderings' => $this->expand(
                fn (RenderedDocumentInterface $rendering) => $rendering->toArray()
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'renderings' => $this->expand(
                fn (RenderedDocumentInterface $rendering) => $rendering->jsonSerialize()
            ),
        ];
    }

    /**
     * Expande cada archivo renderizado en tantas entradas como copias
     * represente, agregando el número de copia y, si hay más de una copia,
     * ajustando el nombre del archivo para no colisionar entre copias.
     *
     * El `content`/`mimeType` de cada entrada se calculan una sola vez (vía
     * `$serializer`), no una vez por copia: expandir es solo repetir el
     * mismo arreglo ya calculado.
     *
     * @param callable(RenderedDocumentInterface): array $serializer
     * @return array
     */
    private function expand(callable $serializer): array
    {
        $expanded = [];

        foreach ($this->renderings as $rendering) {
            $entry = $serializer($rendering);
            $copies = $rendering->getCopies();

            for ($copyNumber = 1; $copyNumber <= $copies; $copyNumber++) {
                $copy = $entry;
                $copy['copyNumber'] = $copyNumber;

                if ($copies > 1 && $copy['filename'] !== null) {
                    $copy['filename'] = $this->appendCopyNumber(
                        $copy['filename'],
                        $copyNumber
                    );
                }

                $expanded[] = $copy;
            }
        }

        return $expanded;
    }

    /**
     * Agrega el número de copia al nombre de un archivo, antes de su
     * extensión (ej. `factura.pdf` => `factura_2.pdf`).
     *
     * @param string $filename
     * @param int $copyNumber
     * @return string
     */
    private function appendCopyNumber(string $filename, int $copyNumber): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);

        if ($extension === '') {
            return sprintf('%s_%d', $base, $copyNumber);
        }

        return sprintf('%s_%d.%s', $base, $copyNumber, $extension);
    }
}
