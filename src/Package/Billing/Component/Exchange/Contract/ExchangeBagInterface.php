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

use Derafu\Lib\Core\Common\Contract\OptionsAwareInterface;

/**
 * Interfaz que almacena sobres, y sus documentos, más los datos para enviar o
 * recibir durante el proceso de intercambio de documentos electrónicos.
 *
 * Contiene los sobres y todas las opciones necesarias para el intercambio.
 */
interface ExchangeBagInterface extends OptionsAwareInterface
{
    /**
     * Agrega un sobre a la bolsa.
     *
     * @param EnvelopeInterface $envelope
     * @return static
     */
    public function addEnvelope(EnvelopeInterface $envelope): static;

    /**
     * Obtiene el listado de sobres que la bolsa tiene.
     *
     * @return EnvelopeInterface[]
     */
    public function getEnvelopes(): array;

    /**
     * Indica si la bolsa tiene o no sobres dentro.
     *
     * @return bool
     */
    public function hasEnvelopes(): bool;

    /**
     * Obtiene los resultados del proceso de intercambio.
     *
     * @return ExchangeResultInterface[]
     */
    public function getResults(): array;

    /**
     * Agrega un resultado del proceso de intercambio.
     *
     * Al agregar un resultado si el sobre del resultado no está en los sobres
     * de la bolsa se espera que se agreguen automáticamente.
     *
     * @param ExchangeResultInterface $result
     * @return static
     */
    public function addResult(ExchangeResultInterface $result): static;
}
