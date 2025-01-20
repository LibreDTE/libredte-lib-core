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

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\SanitizerException;

/**
 * Clase para los sanitizadores.
 */
class SanitizerWorker extends AbstractWorker implements SanitizerWorkerInterface
{
    /**
     * {@inheritDoc}
     */
    public function sanitize(DocumentBagInterface $bag): array
    {
        // Si no hay tipo de documento no se podrá sanitizar.
        if (!$bag->getTipoDocumento()) {
            throw new SanitizerException(
                'No es posible sanitizar sin un TipoDocumento en la $bag.'
            );
        }

        // Buscar la estrategia para sanitizar el tipo de documento tributario.
        $strategy = $this->getStrategy($bag->getTipoDocumento()->getAlias());
        assert($strategy instanceof SanitizerStrategyInterface);

        // Sanitizar el documento usando la estrategia.
        $sanitizedData = $strategy->sanitize($bag);

        // Entregar los datos sanitizados.
        return $sanitizedData;
    }
}
