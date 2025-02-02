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
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;

/**
 * Interfaz para la entidad de tipo de documento tributario.
 */
interface TipoDocumentoInterface extends EntityInterface
{
    /*
    |--------------------------------------------------------------------------
    | Getters.
    |--------------------------------------------------------------------------
    */

    /**
     * Entrega el código del documento.
     */
    public function getCodigo(): int|string;

    /**
     * Entrega el nombre del documento.
     *
     * @return string
     */
    public function getNombre(): string;

    /**
     * Entrega el nombre corto del tipo de documento.
     *
     * @return string
     */
    public function getNombreCorto(): string;

    /**
     * Entrega la categoría del documento.
     *
     * @return CategoriaDocumento|null
     */
    public function getCategoria(): ?CategoriaDocumento;

    /**
     * Indica si el documento es electrónico o no.
     *
     * @return bool|null
     */
    public function esElectronico(): ?bool;

    /**
     * Indica si un documento que es electrónico se debe enviar al SII.
     *
     * @return bool|null
     */
    public function seEnviaAlSii(): ?bool;

    /**
     * Indica si el documento puede ser utilizado en compras de la empresa.
     *
     * @return bool|null
     */
    public function disponibleEnCompras(): ?bool;

    /**
     * Indica si el documento puede ser utilizado en ventas de la empresa.
     *
     * @return bool|null
     */
    public function disponibleEnVentas(): ?bool;

    /**
     * Entrega la operación que representa el documento al ser agrupado con
     * otros documentos (ya sea en ventas o compras).
     *
     * @return OperacionDocumento|null
     */
    public function getOperacion(): ?OperacionDocumento;

    /**
     * Indica si el documento es cedible.
     *
     * @return bool
     */
    public function esCedible(): bool;

    /**
     * Entrega el tag que debe ser usado al construir el XML del documento.
     *
     * Esto es válido solo para documentros tributarios electrónicos.
     *
     * Los posibles valores son:
     *
     *   - `Documento`: para todos los DTE excepto los de abajo.
     *   - `Exportaciones`: para DTE 110, 111 y 112.
     *   - `Liquidacion`: para DTE 43.
     *
     * @return TagXmlDocumento|null
     */
    public function getTagXml(): ?TagXmlDocumento;

    /**
     * Indica si el documento está disponible en LibreDTE para ser usado.
     *
     * @return bool
     */
    public function estaDisponible(): bool;

    /**
     * Entrega el alias del tipo de documento basado en el ID.
     *
     * @return string|null
     */
    public function getAlias(): ?string;

    /**
     * Entrega la interfaz PHP que la clase asociada el tipo de documento debe
     * implementar.
     *
     * @return string|null
     */
    public function getInterface(): ?string;

    /**
     * Entrega el tipo del sobre de documentos que se debe utilizar cuando se
     * requiera realizar el envío de este tipo de documento.
     *
     * @return TipoSobre|null
     */
    public function getTipoSobre(): ?TipoSobre;

    /*
    |--------------------------------------------------------------------------
    | Métodos que entregan información a partir del tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si el documento es de tipo guía de despacho.
     *
     * @return bool
     */
    public function esGuiaDespacho(): bool;

    /**
     * Indica si el documento es de tipo boleta.
     *
     * @return bool
     */
    public function esBoleta(): bool;

    /**
     * Indica si el documento es de exportación.
     *
     * @return bool
     */
    public function esExportacion(): bool;

    /**
     * Indica si el documento es exento.
     *
     * @return bool
     */
    public function esExento(): bool;

    /*
    |--------------------------------------------------------------------------
    | Métodos que indican que campos son requeridos según el tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si el documento requiere o no acuse de recibo la versión impresa.
     *
     * @return bool
     */
    public function requiereAcuseRecibo(): bool;

    /**
     * Indica si el documento requiere el tag "TpoTranVenta" en el XML.
     *
     * @return bool
     */
    public function requiereTpoTranVenta(): bool;

    /*
    |--------------------------------------------------------------------------
    | Métodos que proveen valores por defecto según el tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * Entrega el valor por defecto de la tasa de IVA.
     *
     * La regla es: documentos exentos y de exportación sin IVA. El resto con
     * el valor vigente del IVA. Actualmente un 19%.
     *
     * @return float|false
     */
    public function getDefaultTasaIVA(): float|false;

    /**
     * Entrega el valor del crédito de IVA para empresas constructoras.
     *
     * @return float
     */
    public function getDefaultCredEC(): float|false;

    /**
     * Entrega el valor por defecto del indicador de servicio para el tipo de
     * documento.
     *
     * @return integer|false
     */
    public function getDefaultIndServicio(): int|false;
}
