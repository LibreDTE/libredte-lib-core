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

use Derafu\Lib\Core\Foundation\Contract\StrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;

/**
 * Interfaz para las estrategias del "billing.exchange.receiver".
 */
interface ReceiverStrategyInterface extends StrategyInterface
{
    /**
     * Recibe documentos a través del proceso de intercambio.
     *
     * @param ExchangeBagInterface $bag Bolsa con las opciones para realizar el
     * intercambio de documentos.
     * @return ExchangeResultInterface[] El resultado del intercambio donde cada
     * resultado contiene un sobre con el estado de la recepción.
     */
    public function receive(ExchangeBagInterface $bag): array;

    /**
     * Indica si la estrategia puede recibir documentos con los datos/opciones
     * de una bolsa.
     *
     * @param ExchangeBagInterface $bag
     * @return void
     * @throws ExchangeException Motivo por el que no se puede procesar.
     */
    public function canReceive(ExchangeBagInterface $bag): void;
}
