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

use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;

/**
 * Interfaz para las entidades que representan documentos tributarios.
 */
interface DocumentInterface extends EntityInterface
{
    /**
     * Entrega el documento XML asociado al DTE.
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
     * Genera el documentto XML como string sin encabezado ni saltos de línea
     * al inicio y final.
     *
     * @return string
     * @see XmlInterface::getXml()
     */
    public function getXml(): string;

    /**
     * Entrega el ID asignado al documento.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene el código numérico del documento tributario.
     *
     * @return integer
     */
    public function getCodigo(): int;

    /**
     * Entrega el folio del documento tributario.
     *
     * @return int
     */
    public function getFolio(): int;

    /**
     * Entrega el tipo de documento (como enum) asociado al DTE.
     *
     * @return CodigoDocumento
     */
    public function getTipoDocumento(): CodigoDocumento;

    /**
     * Obtiene el contribuyente emisor del documento.
     *
     * @return array Datos del emisor en el DTE.
     */
    public function getEmisor(): array;

    /**
     * Obtiene el RUT del emisor del documento.
     *
     * @return string
     */
    public function getRutEmisor(): string;

    /**
     * Obtiene el contribuyente receptor del documento.
     *
     * @return array Datos del receptor en el DTE.
     */
    public function getReceptor(): array;

    /**
     * Obtiene el RUT del receptor del documento.
     *
     * @return string
     */
    public function getRutReceptor(): string;

    /**
     * Obtiene la razón social del receptor del documento.
     *
     * @return string
     */
    public function getRazonSocialReceptor(): string;

    /**
     * Entrega la fecha de emisión asignada al documento tributario.
     *
     * Esta es la fecha de emisión informada al SII del documento, no es la
     * fecha de creación real del documento en LibreDTE.
     *
     * @return string
     */
    public function getFechaEmision(): string;

    /**
     * Entrega todos los valores del tag "Totales".
     *
     * @return array
     */
    public function getTotales(): array;

    /**
     * Entrega el monto total del documento.
     *
     * El monto estará en la moneda del documento.
     *
     * @return int|float Monto total del documento.
     */
    public function getMontoTotal(): int|float;

    /**
     * Entrega la moneda asociada al documento.
     *
     * @return string
     */
    public function getMoneda(): string;

    /**
     * Entrega el detalle del documento.
     *
     * Se puede solicitar todo el detalle o el detalle de una línea en
     * específico.
     *
     * @param int|null $index Índice de la línea de detalle solicitada o
     * `null` (por defecto) para obtener todas las líneas.
     * @return array
     */
    public function getDetalle(?int $index = null): array;

    /**
     * Entrega los datos del DTE.
     *
     * Este método estandariza los datos para entregarlos en un formato
     * compatible con los datos de entrada normalizados al crear un DTE.
     *
     * @return array
     */
    public function getDatos(): array;

    /**
     * Entrega el nodo TED aplanado y listo para ser usado en el PDF417.
     *
     * @return string|null
     */
    public function getTED(): ?string;

    /**
     * Entrega un arreglo con una plantilla con la estructura del TED.
     *
     * Esta plantilla se usa luego para crear el TED firmado. Se deberán
     * completar antes de firmar el TED los campos:
     *
     *   - TED.DD.CAF
     *   - TED.DD.TSTED
     *
     * Luego calcular la firma del TED y agregar a:
     *
     *   - TED.FRMT.@value
     *
     * @return array
     */
    public function getPlantillaTED(): array;

    /**
     * Obtiene un elemento del DTE utilizando un selector.
     *
     * @param string $selector Selector del elemento que se desea obtener.
     */
    public function get(string $selector): mixed;

    /**
     * Realiza una consulta XPath al XML del DTE.
     *
     * @param string $query Consulta XPath con marcadores nombrados (ej.: ":param").
     * @param array $params Arreglo de parámetros en formato ['param' => 'value'].
     */
    public function query(string $query, array $params = []): string|array|null;
}
