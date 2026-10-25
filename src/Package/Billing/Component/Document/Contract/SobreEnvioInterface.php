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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\AutorizacionDteInterface;

/**
 * Interfaz para la clase que representa un sobre de envío de documentos al SII
 * o intercambio entre contribuyentes.
 */
interface SobreEnvioInterface
{
    /**
     * Entrega el documento XML asociado al sobre de documentos.
     *
     * @return XmlDocumentInterface
     */
    public function getXmlDocument(): XmlDocumentInterface;

    /**
     * Genera el documentto XML como string incluyendo encabezado.
     *
     * @return string
     * @see XmlDocumentInterface::saveXml()
     */
    public function saveXml(): string;

    /**
     * Entrega el ID asignado al sobre de documentos.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene el RUT del emisor del sobre de documentos.
     *
     * @return string
     */
    public function getRutEmisor(): string;

    /**
     * Obtiene el RUN del mandatario del emisor del sobre de documentos.
     *
     * @return string
     */
    public function getRunMandatario(): string;

    /**
     * Obtiene el RUT del receptor del sobre de documentos.
     *
     * @return string
     */
    public function getRutReceptor(): string;

    /**
     * Obtiene los datos de autorización del emisor del sobre de documentos.
     *
     * @return AutorizacionDteInterface
     */
    public function getAutorizacionDte(): AutorizacionDteInterface;

    /**
     * Obtiene la fecha de la firma del sobre de documentos (timestamp).
     *
     * @return string
     */
    public function getFechaFirma(): string;

    /**
     * Obtiene el resumen de los documentos que vienen en el sobre.
     *
     * Se informa el código del tipo de documento y cantidad de documentos por
     * cada tipo.
     *
     * @return array<int, int>
     */
    public function getResumen(): array;

    /**
     * Obtiene el string XML de los DTE que vienen en el sobre.
     *
     * @return string[]
     */
    public function getXmlDocumentos(): array;
}
