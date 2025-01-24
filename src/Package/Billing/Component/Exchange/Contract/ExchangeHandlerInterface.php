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

use Derafu\Lib\Core\Foundation\Contract\HandlerInterface;

/**
 * Interfaz para los handlers del intercambio.
 *
 * Se usa tanto para los handler de los workers de Receiver como de Sender.
 */
interface ExchangeHandlerInterface extends HandlerInterface
{
    /**
     * Procesa una bolsa de intercambio, sus sobres y documentos.
     *
     * Este método determinará "qué" sobres de la bolsa debe transportar, y si
     * es posible transportarlos los pasará a las estrategias que correspondan
     * para que realicen el intercambio.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres, si corresponde, y
     * las opciones para realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function handle(ExchangeBagInterface $bag): array;
}
