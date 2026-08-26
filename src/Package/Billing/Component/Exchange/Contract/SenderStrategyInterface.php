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

use Derafu\Backbone\Contract\StrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;

/**
 * Interfaz para las estrategias del worker "billing.exchange.sender".
 */
interface SenderStrategyInterface extends StrategyInterface
{
    /**
     * Envía documentos a través del proceso de intercambio.
     *
     * @param ExchangeBagInterface $bag Bolsa con los sobres y opciones para
     * realizar el intercambio de documentos.
     * @return ExchangeResultInterface[] Los resultados de procesar los sobres
     * de la bolsa. Tiene el estado de cada estrategia que procesó el sobre.
     */
    public function send(ExchangeBagInterface $bag): array;

    /**
     * Indica si la estrategia puede enviar un sobre específico.
     *
     * La bolsa se entrega como contexto (para datos comunes a todos los
     * sobres, como las opciones de transporte) — la evaluación es siempre
     * sobre el sobre puntual que se pasa, no sobre la bolsa completa.
     *
     * @param EnvelopeInterface $envelope Sobre que se desea enviar.
     * @param ExchangeBagInterface $bag Bolsa que contiene el sobre, con las
     * opciones comunes a todos los sobres que envíe esta llamada.
     * @return void
     * @throws ExchangeException Motivo por el que no se puede procesar.
     */
    public function canSend(EnvelopeInterface $envelope, ExchangeBagInterface $bag): void;
}
