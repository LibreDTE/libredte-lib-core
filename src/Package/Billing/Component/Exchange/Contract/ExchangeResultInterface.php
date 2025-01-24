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

use Derafu\Lib\Core\Support\Store\Contract\BagInterface;

/**
 * Interfaz para el resultado del proceso de intercambio de un sobre.
 */
interface ExchangeResultInterface
{
    /**
     * Obtiene el sobre asociado a la operación, y resultado, de intercambio.
     *
     * @return EnvelopeInterface
     */
    public function getEnvelope(): EnvelopeInterface;

    /**
     * Entrega los códigos de las estrategias que procesaron el sobre.
     *
     * @return string[]
     */
    public function getStrategies(): array;

    /**
     * Entrega los estados del intercambio de las estrategias que participaron
     * en el intercambio del sobre asociado.
     *
     * @return ExchangeStatusInterface[]
     */
    public function getStatuses(): array;

    /**
     * Agrega el estado de resultado de una estrategia al resultado general del
     * intercambio del sobre.
     *
     * @return static
     */
    public function addStatus(ExchangeStatusInterface $status): static;

    /**
     * Asigna los metadatos del resultado.
     *
     * @param BagInterface|array $metadata
     * @return static
     */
    public function setMetadata(BagInterface|array $metadata): static;

    /**
     * Agrega una clave específica a los metadatos.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addMetadata(string $key, mixed $value): static;

    /**
     * Obtiene los metadatos del resultado.
     *
     * @return BagInterface
     */
    public function getMetadata(): BagInterface;
}
