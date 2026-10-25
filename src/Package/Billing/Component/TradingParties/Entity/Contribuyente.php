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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Entity;

use Derafu\L10n\Cl\Rut\Rut;
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
    protected ?string $razon_social = null;

    /**
     * Giro comercial del contribuyente.
     *
     * @var string|null
     */
    protected ?string $giro = null;

    /**
     * Códigos de actividades económicas del contribuyente.
     *
     * @var int[]
     */
    protected array $actividades_economicas = [];

    /**
     * Teléfonos del contribuyente.
     *
     * @var string[]
     */
    protected array $telefonos = [];

    /**
     * Dirección de correo electrónico del contribuyente.
     *
     * @var string|null
     */
    protected ?string $email = null;

    /**
     * Dirección tributaria del contribuyente.
     *
     * @var string|null
     */
    protected ?string $direccion = null;

    /**
     * Comuna tributaria del contribuyente.
     *
     * @var string|null
     */
    protected ?string $comuna = null;

    /**
     * Ciudad tributaria del contribuyente.
     *
     * @var string|null
     */
    protected ?string $ciudad = null;

    /**
     * Constructor de la clase Contribuyente.
     *
     * @param string|int $rut RUT del contribuyente.
     * @param string|null $razon_social Razón social del contribuyente.
     * @param string|null $giro Giro comercial del contribuyente.
     * @param int|null $actividad_economica Código de actividad económica.
     * @param string|null $telefono Teléfono del contribuyente.
     * @param string|null $email Correo electrónico del contribuyente.
     * @param string|null $direccion Dirección tributaria del contribuyente.
     * @param string|null $comuna Comuna tributaria.
     * @param string|null $ciudad Ciudad tributaria.
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
        ?string $ciudad = null,
    ) {
        $rut = Rut::format($rut);
        [$this->rut, $this->dv] = Rut::toArray($rut);

        if ($razon_social !== null) {
            $this->setRazonSocial($razon_social);
        }

        if ($giro !== null) {
            $this->setGiro($giro);
        }

        if ($actividad_economica !== null) {
            $this->setActividadEconomica($actividad_economica);
        }

        if ($telefono !== null) {
            $this->setTelefono($telefono);
        }

        if ($email !== null) {
            $this->setEmail($email);
        }

        if ($direccion !== null) {
            $this->setDireccion($direccion);
        }

        if ($comuna !== null) {
            $this->setComuna($comuna);
        }

        if ($ciudad !== null) {
            $this->setCiudad($ciudad);
        }

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
    public function setRazonSocial(?string $razon_social): static
    {
        $this->razon_social = trim((string)$razon_social) ?: null;

        return $this;
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
    public function setGiro(?string $giro): static
    {
        $this->giro = trim((string)$giro) ?: null;

        return $this;
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
    public function setActividadEconomica(?int $actividad_economica): static
    {
        $actividad_economica = $actividad_economica ?: null;

        if ($actividad_economica !== null) {
            array_unshift($this->actividades_economicas, $actividad_economica);
            $this->actividades_economicas = array_unique(
                $this->actividades_economicas
            );
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getActividadEconomica(): ?int
    {
        return $this->actividades_economicas[0] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function setTelefono(?string $telefono): static
    {
        $telefono = trim((string)$telefono) ?: null;

        if ($telefono !== null) {
            array_unshift($this->telefonos, $telefono);
            $this->telefonos = array_unique($this->telefonos);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTelefono(): ?string
    {
        return $this->telefonos[0] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function setEmail(?string $email): static
    {
        $this->email = trim((string)$email) ?: null;

        return $this;
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
    public function setDireccion(?string $direccion): static
    {
        $this->direccion = trim((string)$direccion) ?: null;

        return $this;
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
    public function setComuna(?string $comuna): static
    {
        $this->comuna = trim((string)$comuna) ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getComuna(): ?string
    {
        return $this->comuna;
    }

    /**
     * {@inheritDoc}
     */
    public function setCiudad(?string $ciudad): static
    {
        $this->ciudad = trim((string)$ciudad) ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCiudad(): ?string
    {
        return $this->ciudad;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'rut' => $this->getRut(),
            'razon_social' => $this->getRazonSocial(),
            'giro' => $this->getGiro(),
            'actividades_economicas' => $this->getActividadEconomica()
                ? [$this->getActividadEconomica()]
                : []
            ,
            'telefonos' => $this->getTelefono()
                ? [$this->getTelefono()]
                : []
            ,
            'email' => $this->getEmail(),
            'direccion' => $this->getDireccion(),
            'comuna' => $this->getComuna(),
            'ciudad' => $this->getCiudad(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
