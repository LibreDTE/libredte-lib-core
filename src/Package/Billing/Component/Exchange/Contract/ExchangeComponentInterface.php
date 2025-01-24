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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

use Derafu\Lib\Core\Foundation\Contract\ComponentInterface;

/**
 * Interfaz para `ExchangeComponent`.
 */
interface ExchangeComponentInterface extends ComponentInterface
{
    /**
     * Entrega el worker "billing.exchange.receiver".
     *
     * @return ReceiverWorkerInterface
     */
    public function getReceiverWorker(): ReceiverWorkerInterface;

    /**
     * Entrega el worker "billing.exchange.sender".
     *
     * @return SenderWorkerInterface
     */
    public function getSenderWorker(): SenderWorkerInterface;

    /**
     * Recibe documentos a través del proceso de intercambio mediante la
     * estrategia definida en la bolsa.
     *
     * @param ExchangeBagInterface $bag Bolsa con las opciones para realizar el
     * intercambio de documentos.
     * @return ExchangeResultInterface[] El resultado del intercambio donde cada
     * resultado contiene un sobre con el estado de la recepción.
     */
    public function receive(ExchangeBagInterface $bag): array;

    /**
     * Envía documentos a través del proceso de intercambio mediante la
     * estrategia definida en la bolsa.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function send(ExchangeBagInterface $bag): array;

    /**
     * Procesa una bolsa de intercambio, sus sobres y documentos.
     *
     * Este método determinará "qué" sobres de la bolsa debe transportar, y si
     * es posible transportarlos los pasará a las estrategias que correspondan
     * para que realicen el intercambio.
     *
     * Si bien existe receive() y send(), este método es necesario porque se
     * podría querer realizar el transporte utilizando múltiples estrategias
     * disponibles y soportadas por el worker.
     *
     * La decisión de si es un envío o recepción se toma simplemente según si la
     * bolsa tiene o no asignados sobres al ser procesada. Si tiene sobres es un
     * envío, si no tiene es recepción. Se espera que las opciones de la bolsa
     * coincidan con la acción que se realizará.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function handle(ExchangeBagInterface $bag): array;
}
