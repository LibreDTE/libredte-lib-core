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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiConnectionOptions;

/**
 * Clase del lazy worker del SII
 */
class SiiLazyWorker extends AbstractWorker implements SiiLazyWorkerInterface
{
    /**
     * Configuración de la conexión al SII.
     *
     * @var SiiConnectionOptions
     */
    private SiiConnectionOptions $connectionOptions;

    /**
     * {@inheritDoc}
     */
    public function getConnectionOptions(): SiiConnectionOptions
    {
        if (!isset($this->connectionOptions)) {
            $options = (array) $this->getOptions()->get('connection');
            $this->connectionOptions = new SiiConnectionOptions($options);
        }

        return $this->connectionOptions;
    }
}
