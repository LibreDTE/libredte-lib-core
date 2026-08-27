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

namespace libredte\lib\Core\Package\Billing\Component\Document\Support;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\RenderedDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RenderResultInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;

/**
 * Clase para representar el resultado de renderizar un documento.
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
            throw new RendererException(sprintf(
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
            throw new RendererException(sprintf(
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
