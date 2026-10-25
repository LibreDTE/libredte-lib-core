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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Exception;

use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiResponseInterface;
use Throwable;

/**
 * Excepción base para todos los workers del SII.
 */
class SiiException extends IntegrationException
{
    /**
     * Solicitud realizada al SII.
     *
     * @var SiiRequestInterface|null
     */
    protected ?SiiRequestInterface $request = null;

    /**
     * Respuesta obtenida desde el SII.
     *
     * @var SiiResponseInterface|null
     */
    protected ?SiiResponseInterface $response = null;

    /**
     * Constructor de la excepción.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param SiiRequestInterface|null $request
     * @param SiiResponseInterface|null $response
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?SiiRequestInterface $request = null,
        ?SiiResponseInterface $response = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Entrega, si está asignada, la solicitud al SII.
     *
     * @return SiiRequestInterface|null
     */
    public function getRequest(): ?SiiRequestInterface
    {
        return $this->request;
    }

    /**
     * Entrega, si está asignada, la respuesta del SII.
     *
     * @return SiiResponseInterface|null
     */
    public function getResponse(): ?SiiResponseInterface
    {
        return $this->response;
    }
}
