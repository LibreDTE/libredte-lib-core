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

use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\AutorizacionDteInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Trait\CorreoIntercambioDteInfoTrait;

/**
 * Clase para representar un emisor de un documento tributario.
 */
class Emisor extends Contribuyente implements EmisorInterface
{
    // Traits que usa esta entidad.
    use CorreoIntercambioDteInfoTrait;

    /**
     * Código de la sucursal del emisor en el SII.
     *
     * Nota: La casa matriz también tiene código de sucursal asignado por SII.
     *
     * @var int|null
     */
    private ?int $codigo_sucursal = null;

    /**
     * Nombre o código del vendedor que está representando al emisor.
     *
     * @var string|null
     */
    private ?string $vendedor = null;

    /**
     * Entrega el código de la sucursal asignado por el SII al emisor.
     *
     * @return integer|null
     */
    public function getCodigoSucursal(): ?int
    {
        return $this->codigo_sucursal;
    }

    /**
     * Entrega el nombre o código del vendedor que está representando al emisor.
     *
     * @return string|null
     */
    public function getVendedor(): ?string
    {
        return $this->vendedor;
    }

    /**
     * Información de la autorización que da el SII para ser emisor de
     * documentos tributarios electrónicos.
     *
     * La autorización contiene además el ambiente en que se autoriza al emisor.
     *
     * @var AutorizacionDteInterface|null
     */
    private ?AutorizacionDteInterface $autorizacionDte = null;

    /**
     * Logo del emisor.
     *
     * @var string|null
     */
    private ?string $logo = null;

    /**
     * {@inheritDoc}
     */
    public function setAutorizacionDte(AutorizacionDteInterface $autorizacionDte): static
    {
        $this->autorizacionDte = $autorizacionDte;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAutorizacionDte(): ?AutorizacionDteInterface
    {
        return $this->autorizacionDte;
    }

    /**
     * {@inheritDoc}
     */
    public function setLogo(string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }
}
