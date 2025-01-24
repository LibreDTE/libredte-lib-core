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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Abstract;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use Derafu\Lib\Core\Foundation\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;

/**
 * Clase base para los workers de intercambio: ReceiverWorker y SenderWorker.
 */
abstract class AbstractExchangeWorker extends AbstractWorker implements WorkerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(ExchangeBagInterface $bag): array
    {
        // Obtener el listado de handlers que se deben utilizar con la bolsa.
        $handlers = $bag->getOptions()->get('handlers', null)
            ?? array_keys($this->getHandlers())
        ;
        $bag->getOptions()->clear('handlers');

        // Entregar la bolsa a cada handler para que maneje su transporte.
        $results = [];
        foreach ($handlers as $handlerCode) {
            $handler = $this->getHandler($handlerCode);
            assert($handler instanceof ExchangeHandlerInterface);
            $results = array_merge($results, $handler->handle($bag));
        }

        // Entregar el resultado del manejo de la bolsa.
        return $results;
    }
}
