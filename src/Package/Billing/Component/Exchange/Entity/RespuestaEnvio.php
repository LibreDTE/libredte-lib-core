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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Entity;

use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;

/**
 * Entidad que representa el XML `RespuestaDTE`.
 *
 * Puede contener un `RecepcionEnvio` (acuse de recibo del envío) o un
 * `ResultadoDTE` (resultado de la validación por documento). El nodo
 * `Resultado` es el que se firma con ID `LibreDTE_ResultadoEnvio`.
 *
 * Estados para `RecepcionEnvio` (envío):
 *   0 = Envío Recibido Conforme
 *   1 = Envío Rechazado - Error de Schema
 *   2 = Envío Rechazado - Error de Firma
 *   3 = Envío Rechazado - RUT Receptor No Corresponde
 *  90 = Envío Rechazado - Archivo Repetido
 *  91 = Envío Rechazado - Archivo Ilegible
 *  99 = Envío Rechazado - Otros
 *
 * Estados para `RecepcionEnvio.RecepcionDTE` (documento individual):
 *   0 = DTE Recibido OK
 *   1 = DTE No Recibido - Error de Firma
 *   2 = DTE No Recibido - Error en RUT Emisor
 *   3 = DTE No Recibido - Error en RUT Receptor
 *   4 = DTE No Recibido - DTE Repetido
 *  99 = DTE No Recibido - Otros
 *
 * Estados para `ResultadoDTE` (resultado de validación):
 *   0 = ACEPTADO OK
 *   1 = ACEPTADO CON DISCREPANCIAS
 *   2 = RECHAZADO
 */
class RespuestaEnvio extends AbstractExchangeDocument
{
    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return (string) ($this->getXmlDocument()->query('//Resultado/@ID') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): string
    {
        return 'RespuestaEnvioDTE_v10.xsd';
    }

    /**
     * Indica si la respuesta corresponde a un `RecepcionEnvio`.
     *
     * @return bool
     */
    public function isRecepcionEnvio(): bool
    {
        return $this->getXmlDocument()->query('//RecepcionEnvio') !== null;
    }

    /**
     * Indica si la respuesta corresponde a un `ResultadoDTE`.
     *
     * @return bool
     */
    public function isResultadoDTE(): bool
    {
        return $this->getXmlDocument()->query('//ResultadoDTE') !== null;
    }
}
