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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Entity;

use Derafu\Lib\Core\Helper\Rut;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\PersonaNaturalInterface;

/**
 * Clase para representar una persona natural de Chile.
 *
 * Proporciona información básica de una persona natural. Principalmente, los
 * datos básicos necesarios para los certificados digitales.
 */
class PersonaNatural implements PersonaNaturalInterface
{
    /**
     * RUN de la persona natural.
     *
     * @var int
     */
    protected int $run;

    /**
     * Dígito verificador (DV) del RUT.
     *
     * @var string
     */
    protected string $dv;

    /**
     * Nombre de la persona.
     *
     * @var string|null
     */
    protected ?string $nombre;

    /**
     * Dirección de correo electrónico de la persona.
     *
     * @var string|null
     */
    protected ?string $email;

    /**
     * Constructor de la clase PersonaNatural.
     *
     * @param string|int $run RUN del contribuyente.
     * @param string $nombre Nombre de la persona natural.
     * @param string $email Correo electrónico de la persona natural.
     */
    public function __construct(
        string|int $run,
        ?string $nombre = null,
        ?string $email = null
    ) {
        $run = Rut::format($run);
        [$this->run, $this->dv] = Rut::toArray($run);

        $this->nombre = $nombre;
        $this->email = $email;

        // Validar el RUN asignado (independiente del origen).
        Rut::validate($this->getRun());
    }

    /**
     * {@inheritDoc}
     */
    public function getRun(): string
    {
        return $this->run . '-' . $this->dv;
    }

    /**
     * {@inheritDoc}
     */
    public function getNombre(): string
    {
        return $this->nombre ?? $this->getRun();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }
}
