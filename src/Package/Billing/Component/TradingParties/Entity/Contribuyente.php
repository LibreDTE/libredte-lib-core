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
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
