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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Backbone\Contract\StrategiesAwareInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\SanitizerException;

/**
 * Interfaz para los sanitizadores.
 */
interface SanitizerWorkerInterface extends WorkerInterface, StrategiesAwareInterface
{
    /**
     * Realiza la sanitización del documento.
     *
     * @param DocumentBagInterface $bag Contenedor con los datos del documento a sanitizar.
     * @return array Datos sanitizados del documento.
     * @throws SanitizerException
     */
    public function sanitize(DocumentBagInterface $bag): array;
}
