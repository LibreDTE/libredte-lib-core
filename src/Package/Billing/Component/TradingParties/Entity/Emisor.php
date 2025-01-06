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
     * Información de la autorización que da el SII para ser emisor de
     * documentos tributarios electrónicos.
     *
     * La autorización contiene además el ambiente en que se autoriza al emisor.
     *
     * @var AutorizacionDteInterface|null
     */
    private ?AutorizacionDteInterface $autorizacionDte = null;

    /**
     * {@inheritdoc}
     */
    public function setAutorizacionDte(AutorizacionDteInterface $autorizacionDte): static
    {
        $this->autorizacionDte = $autorizacionDte;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAutorizacionDte(): ?AutorizacionDteInterface
    {
        return $this->autorizacionDte;
    }
}
