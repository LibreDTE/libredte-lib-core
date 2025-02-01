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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Contract;

use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiAmbiente;

/**
 * Interfaz para la entidad que representa archivos CAF.
 */
interface CafInterface
{
    /**
     * Obtiene el documento XML.
     *
     * @return XmlInterface
     */
    public function getXmlDocument(): XmlInterface;

    /**
     * Obtiene el documento XML como string.
     *
     * @return string Contenido del XML.
     */
    public function getXml(): string;

    /**
     * Entrega un ID para el CAF generado a partir de los datos del mismo.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene el contribuyente emisor del CAF.
     *
     * @return array Arreglo con el RUT y razón social del contribuyente emisor.
     */
    public function getEmisor(): array;

    /**
     * Obtiene el código del tipo de documento tributario del CAF.
     *
     * @return int Código con el tipo de documento tributario.
     */
    public function getTipoDocumento(): int;

    /**
     * Obtiene el folio inicial autorizado en el CAF.
     *
     * @return int Folio inicial.
     */
    public function getFolioDesde(): int;

    /**
     * Obtiene el folio final autorizado en el CAF.
     *
     * @return int Folio final.
     */
    public function getFolioHasta(): int;

    /**
     * Obtiene la cantidad de folios autorizados en el CAF.
     *
     * @return int Cantidad de folios.
     */
    public function getCantidadFolios(): int;

    /**
     * Determina si el folio pasado como argumento está o no dentro del rango
     * del CAF.
     *
     * NOTE: Esta validación NO verifica si el folio ya fue usado, solo si está
     * dentro del rango de folios disponibles en el CAF.
     *
     * @param int $folio
     * @return bool
     */
    public function enRango(int $folio): bool;

    /**
     * Obtiene la fecha de autorización del CAF.
     *
     * @return string Fecha de autorización en formato YYYY-MM-DD.
     */
    public function getFechaAutorizacion(): string;

    /**
     * Obtiene la fecha de vencimiento del CAF.
     *
     * @return string|null Fecha de vencimiento en formato YYYY-MM-DD o `null`
     * si no aplica.
     */
    public function getFechaVencimiento(): ?string;

    /**
     * Entrega la cantidad de meses que han pasado desde la solicitud del CAF.
     *
     * @return float Cantidad de meses transcurridos.
     */
    public function getMesesAutorizacion(): float;

    /**
     * Indica si el CAF está o no vigente.
     *
     * @param string $timestamp Marca de tiempo para consultar vigencia en un
     * momento específico. Si no se indica, por defecto es la fecha y hora
     * actual.
     * @return bool `true` si el CAF está vigente, `false` si no está vigente.
     */
    public function vigente(?string $timestamp = null): bool;

    /**
     * Indica si el CAF de este tipo de documento vence o no.
     *
     * @return bool `true` si los folios de este tipo de documento vencen,
     * `false` si no vencen.
     */
    public function vence(): bool;

    /**
     * Obtiene el identificador del certificado utilizado en el CAF.
     *
     * @return int ID del certificado.
     */
    public function getIdk(): int;

    /**
     * Entrega el ambiente del SII asociado al CAF.
     *
     * El resultado puede ser:
     *
     *   - `null`: no hay ambiente, pues el Caf es falso y tiene IDK CafFaker::IDK
     *
     * @return SiiAmbiente|null
     */
    public function getAmbiente(): ?SiiAmbiente;

    /**
     * Indica si el CAF es de certificación o producción.
     *
     * El resultado puede ser:
     *
     *   - SiiAmbiente::CERTIFICACION->value es CAF de certificación.
     *   - SiiAmbiente::PRODUCCION->value es CAF de producción.
     *   - `null`: indicando que el Caf es falso y tiene IDK CafFaker::IDK
     *
     * @return int|null
     */
    public function getCertificacion(): ?int;

    /**
     * Entrega los datos del código de autorización de folios (CAF).
     *
     * @return array
     */
    public function getAutorizacion(): array;

    /**
     * Obtiene la clave pública proporcionada en el CAF.
     *
     * @return string Clave pública.
     */
    public function getPublicKey(): string;

    /**
     * Obtiene la clave privada proporcionada en el CAF.
     *
     * @return string Clave privada.
     */
    public function getPrivateKey(): string;

    /**
     * Obtiene la firma del SII sobre el nodo DA del CAF.
     *
     * @return string Firma en base64.
     */
    public function getFirma(): string;
}
