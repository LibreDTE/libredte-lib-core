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

use Derafu\Lib\Core\Foundation\Abstract\AbstractHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;

/**
 * Clase base para los handlers de receoción del proceso de intercambio.
 */
abstract class AbstractReceiverHandler extends AbstractHandler implements ExchangeHandlerInterface
{
    /**
     * Constructor del handler.
     *
     * @param ReceiverWorkerInterface $receiverWorker
     * @param array $strategies Estrategias que este handler puede manejar.
     */
    public function __construct(
        private ReceiverWorkerInterface $receiverWorker,
        iterable $strategies = []
    ) {
        parent::__construct($strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ExchangeBagInterface $bag): array
    {
        // Verificar que la bolsa tenga lo necesario.
        // Esta es una revisión general del handler. Evita solicitar la
        // estrategia si no se debe procesar por este handler.
        if (!$this->hasRequiredData($bag)) {
            return [];
        }

        // Determinar qué estrategias se ejecutarán.
        $strategies = $this->resolveStrategies($bag);
        if (empty($strategies)) {
            return [];
        }

        // Iterar las estrategias.
        foreach ($strategies as $strategy) {
            // Recibir sobres usando el método receive() del worker.
            $bag->getOptions()->set('strategy', $strategy);
            $workerResults = $this->receiverWorker->receive($bag);

            // Agregar los resultados del worker a los resultados generales del
            // handler.
            foreach ($workerResults as $workerResult) {
                $bag->addResult($workerResult);
            }
        }

        // Entregar los resultados de la recepción con todas las estrategias que
        // se hayan ejecutado.
        return $bag->getResults();
    }

    /**
     * Determina si la bolsa tiene los datos mínimos necesarios.
     *
     * Estos datos mínimos son independientes de la estrategia que use el
     * handler, pero están relacionados. Por ejemplo, estrategias que reciben
     * los documentos por correo electrónico requerirán los datos del
     * transporte.
     *
     * @param ExchangeBagInterface $bag
     * @return bool
     */
    abstract protected function hasRequiredData(ExchangeBagInterface $bag): bool;

    /**
     * Entrega las estrategias que efectivamente se pueden ejecutar con
     * la bolsa que se ha pasado.
     *
     * Este método revisa cada estrategia pasando la bolsa para saber si la
     * estrategia la puede procesar.
     *
     * @param ExchangeBagInterface $bag
     * @return string[] Códigos de las estrategias que se pueden ejecutar.
     */
    protected function resolveStrategies(ExchangeBagInterface $bag): array
    {
        $strategies = [];

        foreach ($this->getStrategies() as $name => $strategy) {
            assert($strategy instanceof ReceiverStrategyInterface);
            try {
                $strategy->canReceive($bag);
                $strategies[] = $name;
            } catch (ExchangeException $e) {
                // Falla silenciosamente pues no se puede procesar con esta
                // estrategia.
            }
        }

        return $strategies;
    }
}
