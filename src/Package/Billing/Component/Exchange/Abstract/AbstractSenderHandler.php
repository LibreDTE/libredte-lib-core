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
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use LogicException;

/**
 * Clase base para los handlers de envío del proceso de intercambio.
 */
abstract class AbstractSenderHandler extends AbstractHandler implements ExchangeHandlerInterface
{
    /**
     * Constructor del handler.
     *
     * @param SenderWorkerInterface $senderWorker
     * @param array $strategies Estrategias que este handler puede manejar.
     */
    public function __construct(
        private SenderWorkerInterface $senderWorker,
        iterable $strategies = []
    ) {
        parent::__construct($strategies);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ExchangeBagInterface $bag): array
    {
        // Si no hay sobres error.
        if (!$bag->hasEnvelopes()) {
            throw new LogicException(
                'La bolsa de intercambio que se desea enviar no posee sobres.'
            );
        }

        // Procesar cada sobre.
        foreach ($bag->getEnvelopes() as $envelope) {
            // Si no hay documentos error.
            if (!$envelope->countDocuments()) {
                throw new LogicException(
                    'El sobre que se desea enviar no posee documentos.'
                );
            }

            // Verificar si este sobre debe ser procesado por el handler.
            // Esta es una revisión general del handler. Evita solicitar la
            // estrategia si no se debe procesar por este handler.
            if (!$this->shouldProcess($envelope)) {
                continue;
            }

            // Verificar que el sobre tenga lo necesario.
            // Esta es una revisión general del handler. Evita solicitar la
            // estrategia si no se debe procesar por este handler.
            if (!$this->hasRequiredData($envelope)) {
                continue;
            }

            // Determinar qué estrategias se ejecutarán.
            $strategies = $this->resolveStrategies($envelope);
            if (empty($strategies)) {
                continue;
            }

            // Iterar las estrategias.
            foreach ($strategies as $strategy) {
                // Armar la bolsa para el worker. Se reutilizan las opciones y
                // se pasa solo el sobre que se está procesando.
                $bagClass = get_class($bag);
                $bagForWorker = new $bagClass($bag->getOptions()->all());
                assert($bagForWorker instanceof ExchangeBagInterface);
                $bagForWorker->addEnvelope($envelope);
                $bagForWorker->getOptions()->set('strategy', $strategy);

                // Enviar el sobre usando el método send() del worker.
                $workerResults = $this->senderWorker->send($bagForWorker);

                // La llamada previa a send() entrega un arreglo, pero como se
                // pasó solo un sobre habrá solo un resultado del worker. Este
                // resultado se agrega a los resultados generales del handler.
                $bag->addResult($workerResults[0]);
            }
        }

        // Entregar los resultados del envío de todos los sobres con todas las
        // estrategias que se hayan ejecutado.
        return $bag->getResults();
    }

    /**
     * Determina si el sobre debe ser procesado por el handler y pasado a una
     * estrategia si tiene los datos necesarios y se encuentra una estrategia
     * válida.
     *
     * @param EnvelopeInterface $envelope
     * @return bool
     */
    abstract protected function shouldProcess(EnvelopeInterface $envelope): bool;

    /**
     * Determina si el sobre tiene los datos mínimos necesarios.
     *
     * Estos datos mínimos son independientes de la estrategia que use el
     * handler, pero están relacionados. Por ejemplo, estrategias que envían el
     * sobre por correo electrónico requerirán un correo electrónico en el
     * destinatario.
     *
     * @param EnvelopeInterface $envelope
     * @return bool
     */
    abstract protected function hasRequiredData(EnvelopeInterface $envelope): bool;

    /**
     * Entrega las estrategias que efectivamente se pueden ejecutar con el sobre
     * que se ha pasado.
     *
     * Este método revisa cada estrategia pasando el sobre para saber si la
     * estrategia lo puede procesar.
     *
     * @param EnvelopeInterface $envelope
     * @return string[] Códigos de las estrategias que se pueden ejecutar.
     */
    protected function resolveStrategies(EnvelopeInterface $envelope): array
    {
        $strategies = [];

        foreach ($this->getStrategies() as $name => $strategy) {
            assert($strategy instanceof SenderStrategyInterface);
            try {
                $strategy->canSend($envelope);
                $strategies[] = $name;
            } catch (ExchangeException $e) {
                // Falla silenciosamente pues no se puede procesar con esta
                // estrategia.
            }
        }

        return $strategies;
    }
}
