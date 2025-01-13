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

namespace libredte\lib\Core\Package\Billing\Component\Document\Entity;

use Derafu\Lib\Core\Package\Prime\Component\Entity\Entity\Entity;

/**
 * Entidad de etiqueta XML.
 */
class TagXml extends Entity
{
    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getAttribute('glosa');
    }

    /**
     * Entrega la entidad asociada al tag XML si existe una.
     *
     * La entidad estará disponible cuando exista un repositorio asociado que
     * maneje el tag XML (sus glosas).
     *
     * @return string|null
     */
    public function getEntity(): ?string
    {
        if (!$this->hasAttribute('entity')) {
            return null;
        }

        return $this->getAttribute('entity');
    }
}
