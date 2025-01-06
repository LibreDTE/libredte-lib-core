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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
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
     * @return XmlInterface
     */
    public function getXmlDocument(): XmlInterface;

    /**
     * Genera el documentto XML como string incluyendo encabezado.
     *
     * @return string
     * @see XmlInterface::saveXml()
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
