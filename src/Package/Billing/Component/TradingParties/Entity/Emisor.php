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
     * Sucursal del emisor.
     *
     * @var string|null
     */
    private ?string $sucursal = null;

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
     * Información de la autorización que da el SII para ser emisor de
     * documentos tributarios electrónicos.
     *
     * La autorización contiene además el ambiente en que se autoriza al emisor.
     *
     * @var AutorizacionDteInterface|null
     */
    private ?AutorizacionDteInterface $autorizacion_dte = null;

    /**
     * Logo del emisor.
     *
     * @var string|null
     */
    private ?string $logo = null;

    /**
     * Constructor de la clase Contribuyente.
     *
     * @param string|int $rut RUT del contribuyente.
     * @param string|null $razon_social Razón social del contribuyente.
     * @param string|null $giro Giro comercial del contribuyente.
     * @param int|array|null $actividad_economica Código de actividad económica.
     * @param string|array|null $telefono Teléfonos del contribuyente.
     * @param string|null $email Correo electrónico del contribuyente.
     * @param string|null $direccion Dirección tributaria del contribuyente.
     * @param string|null $comuna Comuna tributaria.
     * @param string|null $ciudad Ciudad tributaria.
     * @param string|null $sucursal Sucursal del emisor.
     * @param int|null $codigo_sucursal Código de la sucursal del emisor en el
     * SII.
     * @param string|null $vendedor Nombre o código del vendedor que está
     * representando al emisor.
     * @param AutorizacionDteInterface|null $autorizacion_dte Información de la
     * autorización que da el SII para ser emisor de documentos tributarios
     * electrónicos.
     * @param string|null $logo Logo del emisor.
     */
    public function __construct(
        string|int $rut,
        ?string $razon_social = null,
        ?string $giro = null,
        int|array|null $actividad_economica = null,
        string|array|null $telefono = null,
        ?string $email = null,
        ?string $direccion = null,
        ?string $comuna = null,
        ?string $ciudad = null,
        ?string $sucursal = null,
        ?int $codigo_sucursal = null,
        ?string $vendedor = null,
        ?AutorizacionDteInterface $autorizacion_dte = null,
        ?string $logo = null,
    ) {
        parent::__construct(
            rut: $rut,
            razon_social: $razon_social,
            giro: $giro,
            email: $email,
            direccion: $direccion,
            comuna: $comuna,
            ciudad: $ciudad
        );

        if ($actividad_economica !== null) {
            if (is_array($actividad_economica)) {
                $this->setActividadesEconomicas($actividad_economica);
            } else {
                $this->setActividadEconomica($actividad_economica);
            }
        }

        if ($telefono !== null) {
            if (is_array($telefono)) {
                $this->setTelefonos($telefono);
            } else {
                $this->setTelefono($telefono);
            }
        }

        if ($sucursal !== null) {
            $this->setSucursal($sucursal);
        }

        if ($codigo_sucursal !== null) {
            $this->setCodigoSucursal($codigo_sucursal);
        }

        if ($vendedor !== null) {
            $this->setVendedor($vendedor);
        }

        if ($autorizacion_dte !== null) {
            $this->setAutorizacionDte($autorizacion_dte);
        }

        if ($logo !== null) {
            $this->setLogo($logo);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addActividadEconomica(int $actividad_economica): static
    {
        if (!in_array($actividad_economica, $this->actividades_economicas)) {
            $this->actividades_economicas[] = $actividad_economica;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setActividadesEconomicas(array $actividades_economicas): static
    {
        $this->actividades_economicas = $actividades_economicas;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getActividadesEconomicas(): array
    {
        return $this->actividades_economicas;
    }

    /**
     * {@inheritDoc}
     */
    public function addTelefono(string $telefono): static
    {
        if (!in_array($telefono, $this->telefonos)) {
            $this->telefonos[] = $telefono;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTelefonos(array $telefonos): static
    {
        $this->telefonos = $telefonos;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTelefonos(): array
    {
        return $this->telefonos;
    }

    /**
     * {@inheritDoc}
     */
    public function setSucursal(?string $sucursal): static
    {
        $this->sucursal = trim((string)$sucursal) ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSucursal(): ?string
    {
        return $this->sucursal;
    }

    /**
     * {@inheritDoc}
     */
    public function setCodigoSucursal(?int $codigo_sucursal): static
    {
        $this->codigo_sucursal = $codigo_sucursal ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCodigoSucursal(): ?int
    {
        return $this->codigo_sucursal;
    }

    /**
     * {@inheritDoc}
     */
    public function setVendedor(?string $vendedor): static
    {
        $this->vendedor = trim((string)$vendedor) ?: null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getVendedor(): ?string
    {
        return $this->vendedor;
    }

    /**
     * {@inheritDoc}
     */
    public function setAutorizacionDte(?AutorizacionDteInterface $autorizacion_dte): static
    {
        $this->autorizacion_dte = $autorizacion_dte;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAutorizacionDte(): ?AutorizacionDteInterface
    {
        return $this->autorizacion_dte;
    }

    /**
     * {@inheritDoc}
     */
    public function setLogo(?string $logo): static
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

    /**
     * {@inheritDoc}
     */
    public function toDteArray(): array
    {
        // Determinar el tag Acteco del XML del DTE.
        $Acteco = $this->getActividadesEconomicas();
        if (isset($Acteco[0])) {
            if (isset($Acteco[1])) {
                $Acteco = array_slice($Acteco, 0, 4);
            } else {
                $Acteco = $Acteco[0];
            }
        } else {
            $Acteco = false;
        }

        // Determinar el tag Telefono del XML del DTE.
        $Telefono = $this->getTelefonos();
        if (isset($Telefono[0])) {
            if (isset($Telefono[1])) {
                $Telefono = array_slice($Telefono, 0, 2);
            } else {
                $Telefono = $Telefono[0];
            }
        } else {
            $Telefono = false;
        }

        // Entregar los datos del emisor como el nodo Emisor del XML del DTE.
        return [
            'RUTEmisor' => $this->getRut(),
            'RznSoc' => $this->getRazonSocial(),
            'GiroEmis' => $this->getGiro() ?? false,
            'Telefono' => $Telefono,
            'CorreoEmisor' => $this->getEmail()
                ?? $this->getCorreoIntercambioDte()
                ?? false
            ,
            'Acteco' => $Acteco,
            // 'GuiaExport' => $this->,
            'Sucursal' => $this->getSucursal() ?? false,
            'CdgSIISucur' => $this->getCodigoSucursal() ?? false,
            'DirOrigen' => $this->getDireccion() ?? false,
            'CmnaOrigen' => $this->getComuna() ?? false,
            'CiudadOrigen' => $this->getCiudad() ?? false,
            'CdgVendedor' => $this->getVendedor() ?? false,
            // 'IdAdicEmisor' => $this->,
            // 'RUTProveedor' => $this->,
            // 'RznSocProveedor' => $this->,
        ];
    }
}
