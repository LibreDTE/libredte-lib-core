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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ContribuyenteInterface;

/**
 * Clase para representar un contribuyente del SII de Chile.
 *
 * Proporciona información básica del contribuyente, como su RUT, razón social,
 * giro, entre otros.
 */
class Contribuyente implements ContribuyenteInterface
{
    /**
     * RUT del contribuyente.
     *
     * @var int
     */
    protected int $rut;

    /**
     * Dígito verificador (DV) del RUT.
     *
     * @var string
     */
    protected string $dv;

    /**
     * Razón social del contribuyente.
     *
     * @var string|null
     */
    protected ?string $razon_social;

    /**
     * Giro comercial del contribuyente.
     *
     * @var string|null
     */
    protected ?string $giro;

    /**
     * Código de actividad económica del contribuyente.
     *
     * @var int|null
     */
    protected ?int $actividad_economica;

    /**
     * Teléfono del contribuyente.
     *
     * @var string|null
     */
    protected ?string $telefono;

    /**
     * Dirección de correo electrónico del contribuyente.
     *
     * @var string|null
     */
    protected ?string $email;

    /**
     * Dirección física del contribuyente.
     *
     * @var string|null
     */
    protected ?string $direccion;

    /**
     * Comuna de residencia del contribuyente.
     *
     * @var string|null
     */
    protected ?string $comuna;

    /**
     * Constructor de la clase Contribuyente.
     *
     * @param string|int $rut RUT del contribuyente.
     * @param string|null $razon_social Razón social del contribuyente.
     * @param string|null $giro Giro comercial del contribuyente.
     * @param int|null $actividad_economica Código de actividad económica.
     * @param string|null $telefono Teléfono del contribuyente.
     * @param string|null $email Correo electrónico del contribuyente.
     * @param string|null $direccion Dirección física del contribuyente.
     * @param string|null $comuna Comuna de residencia.
     */
    public function __construct(
        string|int $rut,
        ?string $razon_social = null,
        ?string $giro = null,
        ?int $actividad_economica = null,
        ?string $telefono = null,
        ?string $email = null,
        ?string $direccion = null,
        ?string $comuna = null,
    ) {
        $rut = Rut::format($rut);
        [$this->rut, $this->dv] = Rut::toArray($rut);

        $this->razon_social = $razon_social ?: null;
        $this->giro = $giro ?: null;
        $this->actividad_economica = $actividad_economica ?: null;
        $this->telefono = $telefono ?: null;
        $this->email = $email ?: null;
        $this->direccion = $direccion ?: null;
        $this->comuna = $comuna ?: null;

        // Validar el RUT asignado (independiente del origen).
        Rut::validate($this->getRut());
    }

    /**
     * {@inheritDoc}
     */
    public function getRutAsInt(): int
    {
        return $this->rut;
    }

    /**
     * {@inheritDoc}
     */
    public function getRut(): string
    {
        return $this->rut . '-' . $this->dv;
    }

    /**
     * {@inheritDoc}
     */
    public function getRazonSocial(): string
    {
        return $this->razon_social ?? $this->getRut();
    }

    /**
     * {@inheritDoc}
     */
    public function getGiro(): ?string
    {
        return $this->giro;
    }

    /**
     * {@inheritDoc}
     */
    public function getActividadEconomica(): ?int
    {
        return $this->actividad_economica;
    }

    /**
     * {@inheritDoc}
     */
    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    /**
     * {@inheritDoc}
     */
    public function getComuna(): ?string
    {
        return $this->comuna;
    }
}
