<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support;

use Countable;
use Generator;
use IteratorAggregate;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;

/**
 * Intentos de una operación contra el SII.
 *
 * Se crea una instancia por operación y se recorre con `foreach`. Cada
 * iteración es un intento y entrega los segundos que ese intento puede tardar
 * como máximo, que es lo menor entre el tiempo por intento de la solicitud y
 * el tiempo que queda de la duración total.
 *
 * Entre un intento y el siguiente hace una pausa con "exponential backoff"
 * (0,5 s, 1 s, 2 s, ...), acotada al tiempo que queda. No hay pausa después
 * del último intento. El recorrido termina al llegar a la cantidad de
 * intentos o cuando el tiempo que queda no alcanza para otro intento.
 *
 * @implements IteratorAggregate<int, float>
 */
class SiiAttempts implements IteratorAggregate, Countable
{
    /**
     * Segundos mínimos que debe quedar para que se haga otro intento.
     */
    private const MIN_ATTEMPT_TIME = 1.0;

    /**
     * Segundos de la pausa antes del segundo intento. En los siguientes se
     * duplica.
     */
    private const BASE_PAUSE = 0.5;

    /**
     * Intentos realizados hasta ahora.
     *
     * @var int<0, max>
     */
    private int $attempts = 0;

    /**
     * @param int $retries Cantidad máxima de intentos.
     * @param int $timeout Segundos máximos de cada intento.
     * @param int $maxDuration Segundos máximos de todos los intentos y las
     * pausas entre ellos.
     */
    public function __construct(
        private readonly int $retries,
        private readonly int $timeout,
        private readonly int $maxDuration
    ) {
    }

    /**
     * Crea los intentos de una operación a partir de la solicitud al SII.
     *
     * @param SiiRequestInterface $request Solicitud al SII.
     * @param int|null $retries Cantidad máxima de intentos que reemplaza a la
     * de la solicitud, si se indica.
     */
    public static function fromRequest(
        SiiRequestInterface $request,
        ?int $retries = null
    ): self {
        return new self(
            $request->getRetries($retries),
            $request->getTimeout(),
            $request->getMaxDuration()
        );
    }

    /**
     * Entrega los segundos máximos de cada intento, uno por iteración.
     *
     * @return Generator<int, float>
     */
    public function getIterator(): Generator
    {
        $start = microtime(true);
        $this->attempts = 0;

        for ($attempt = 0; $attempt < $this->retries; $attempt++) {
            if ($attempt > 0) {
                $pause = min(
                    self::BASE_PAUSE * (2 ** ($attempt - 1)),
                    max(0.0, $this->getRemaining($start) - self::MIN_ATTEMPT_TIME)
                );
                if ($pause > 0.0) {
                    usleep((int) round($pause * 1_000_000));
                }
            }

            $remaining = $this->getRemaining($start);
            if ($remaining < self::MIN_ATTEMPT_TIME) {
                return;
            }

            $this->attempts++;

            yield $attempt => min((float) $this->timeout, $remaining);
        }
    }

    /**
     * Entrega la cantidad de intentos realizados.
     */
    public function count(): int
    {
        return $this->attempts;
    }

    /**
     * Segundos que quedan de la duración total.
     */
    private function getRemaining(float $start): float
    {
        return $this->maxDuration - (microtime(true) - $start);
    }
}
