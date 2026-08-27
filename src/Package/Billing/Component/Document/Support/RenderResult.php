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
     * Constructor del resultado del renderizado.
     *
     * @param RenderedDocumentInterface ...$renderings Archivos generados
     * por el renderizado.
     */
    public function __construct(RenderedDocumentInterface ...$renderings)
    {
        $this->renderings = $renderings;
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
            'renderings' => array_map(
                fn (RenderedDocumentInterface $rendering) => $rendering->toArray(),
                $this->renderings
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'renderings' => array_map(
                fn (RenderedDocumentInterface $rendering) => $rendering->jsonSerialize(),
                $this->renderings
            ),
        ];
    }
}
