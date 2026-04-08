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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Entity;

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
