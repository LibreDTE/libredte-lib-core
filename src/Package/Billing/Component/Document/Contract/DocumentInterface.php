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

use Derafu\Repository\Contract\EntityInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;

/**
 * Interfaz para las entidades que representan documentos tributarios.
 */
interface DocumentInterface extends EntityInterface, JsonSerializable
{
    /**
     * Entrega el documento XML asociado al DTE.
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
     * Genera el documentto XML como string sin encabezado ni saltos de línea
     * al inicio y final.
     *
     * @return string
     * @see XmlDocumentInterface::getXml()
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
     * @return int
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
     * Obtiene el código de la sucursal del emisor en el SII.
     *
     * @return int|null
     */
    public function getSucursalSii(): ?int;

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
     * @return string|null
     */
    public function getRazonSocialReceptor(): ?string;

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
     * Entrega el monto exento del documento.
     *
     * El monto estará en la moneda del documento.
     *
     * En documentos de exportación el monto será entregado como `float`, en
     * otros tipos de documentos será entregado como `int`.
     *
     * @return int|float|null
     */
    public function getMontoExento(): int|float|null;

    /**
     * Entrega el monto neto del documento.
     *
     * @return int|null
     */
    public function getMontoNeto(): ?int;

    /**
     * Entrega el monto de IVA del documento.
     *
     * @return int|null
     */
    public function getMontoIVA(): ?int;

    /**
     * Entrega el monto total del documento.
     *
     * El monto estará en la moneda del documento.
     *
     * En documentos exentos el monto será entregado como `float`, en otros
     * tipos de documentos será entregado como `int`.
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
     * Entrega el monto exento del documento.
     *
     * El monto estará siempre en la moneda CLP.
     *
     * Si el documento es de exportación y está en moneda extranjera, se
     * convertirá a CLP usando el tipo de cambio informado en el documento.
     *
     * @return int|null
     */
    public function getExento(): ?int;

    /**
     * Entrega el monto neto del documento.
     *
     * Es equivalente a llamar a {@see DocumentInterface::getMontoNeto()}.
     *
     * @return int|null
     */
    public function getNeto(): ?int;

    /**
     * Entrega el monto de IVA del documento.
     *
     * Es equivalente a llamar a {@see DocumentInterface::getMontoIVA()}.
     *
     * @return int|null
     */
    public function getIVA(): ?int;

    /**
     * Entrega el monto total del documento.
     *
     * El monto estará siempre en la moneda CLP.
     *
     * Si el documento es de exportación y está en moneda extranjera, se
     * convertirá a CLP usando el tipo de cambio informado en el documento.
     *
     * @return int
     */
    public function getTotal(): int;

    /**
     * Entrega el tipo de cambio asociado a una moneda.
     *
     * Solo tiene sentido en documentos que están en moneda extranjera.
     *
     * @param string $moneda Moneda a la que se desea obtener el tipo de cambio.
     * @return float|null
     */
    public function getTipoDeCambio(string $moneda = 'PESO CL'): ?float;

    /**
     * Convierte un monto a pesos chilenos.
     *
     * Solo tiene sentido en documentos que están en moneda extranjera.
     *
     * @param int|float $value Monto a convertir.
     * @param string|null $moneda Moneda a la que se desea convertir el monto.
     * @return int|float
     */
    public function convertirAPesosCL(int|float $value, ?string $moneda = null): int|float;

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
    public function getData(): array;

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
    public function getTemplateTED(): array;

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

    /**
     * Entrega los datos del DTE como un arreglo.
     *
     * @return array
     */
    public function toArray(): array;
}
