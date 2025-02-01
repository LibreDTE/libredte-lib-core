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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Exception;

use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiResponseInterface;
use Throwable;

/**
 * Excepción para problemas del lazy worker del SII.
 */
class SiiLazyException extends IntegrationException
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
     * Entrega, si está asignada, la solicitud al SII.
     *
     * @return SiiResponseInterface|null
     */
    public function getResponse(): ?SiiResponseInterface
    {
        return $this->response;
    }
}
