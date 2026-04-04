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

use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Trait\CorreoIntercambioDteInfoTrait;

/**
 * Clase para representar un receptor de un documento tributario.
 */
class Receptor extends Contribuyente implements ReceptorInterface
{
    // Traits que usa esta entidad.
    use CorreoIntercambioDteInfoTrait;

    /**
     * Código interno del receptor.
     *
     * @var string|null
     */
    protected ?string $codigo_interno = null;

    /**
     * Nacionalidad del receptor.
     *
     * @var string|null
     */
    protected ?string $nacionalidad = null;

    /**
     * Identificador extranjero del receptor.
     */
    protected ?string $identificador_extranjero = null;

    /**
     * Constructor de la clase Receptor.
     *
     * @param string|int $rut RUT del receptor.
     * @param string|null $razon_social Razón social del receptor.
     * @param string|null $giro Giro comercial del receptor.
     * @param int|null $actividad_economica Código de actividad económica del receptor.
     * @param string|null $telefono Teléfono del receptor.
     * @param string|null $email Correo electrónico del receptor.
     * @param string|null $direccion Dirección tributaria del receptor.
     * @param string|null $comuna Comuna tributaria del receptor.
     * @param string|null $ciudad Ciudad tributaria del receptor.
     * @param string|null $codigo_interno Código interno del receptor.
     * @param string|null $nacionalidad Nacionalidad del receptor.
     * @param string|null $identificador_extranjero Identificador extranjero del receptor.
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
        ?string $codigo_interno = null,
        ?string $nacionalidad = null,
        ?string $identificador_extranjero = null,
    ) {
        parent::__construct(
            rut: $rut,
            razon_social: $razon_social,
            giro: $giro,
            actividad_economica: $actividad_economica,
            telefono: $telefono,
            email: $email,
            direccion: $direccion,
            comuna: $comuna,
            ciudad: $ciudad
        );

        if ($codigo_interno !== null) {
            $this->setCodigoInterno($codigo_interno);
        }

        if ($nacionalidad !== null) {
            $this->setNacionalidad($nacionalidad);
        }

        if ($identificador_extranjero !== null) {
            $this->setIdentificadorExtranjero($identificador_extranjero);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setCodigoInterno(?string $codigo_interno): static
    {
        $this->codigo_interno = trim((string)$codigo_interno) ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCodigoInterno(): ?string
    {
        return $this->codigo_interno;
    }

    /**
     * {@inheritDoc}
     */
    public function setNacionalidad(?string $nacionalidad): static
    {
        $this->nacionalidad = trim((string)$nacionalidad) ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getNacionalidad(): ?string
    {
        return $this->nacionalidad;
    }

    /**
     * {@inheritDoc}
     */
    public function setIdentificadorExtranjero(?string $identificador_extranjero): static
    {
        $this->identificador_extranjero = trim((string)$identificador_extranjero) ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentificadorExtranjero(): ?string
    {
        return $this->identificador_extranjero;
    }

    /**
     * {@inheritDoc}
     */
    public function toDteArray(): array
    {
        // Determinar el tag Extranjero del XML del DTE.
        if ($this->getNacionalidad() !== null || $this->getIdentificadorExtranjero() !== null) {
            $Extranjero = [
                'NumId' => $this->getIdentificadorExtranjero() ?? false,
                'Nacionalidad' => $this->getNacionalidad() ?? false,
            ];
        } else {
            $Extranjero = false;
        }

        // Entregar los datos del receptor como el nodo Receptor del XML del DTE.
        return [
            'RUTRecep' => $this->getRut(),
            'CdgIntRecep' => $this->getCodigoInterno(),
            'RznSocRecep' => $this->getRazonSocial(),
            'Extranjero' => $Extranjero,
            'GiroRecep' => $this->getGiro() ?? false,
            'Contacto' => $this->getTelefono() ?? false,
            'CorreoRecep' => $this->getEmail()
                ?? $this->getCorreoIntercambioDte()
                ?? false
            ,
            'DirRecep' => $this->getDireccion() ?? false,
            'CmnaRecep' => $this->getComuna() ?? false,
            'CiudadRecep' => $this->getCiudad() ?? false,
            // 'DirPostal' => $this->,
            // 'CmnaPostal' => $this->,
            // 'CiudadPostal' => $this->,
        ];
    }
}
