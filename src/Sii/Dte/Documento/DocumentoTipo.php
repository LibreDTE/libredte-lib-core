<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\Documento;

use libredte\lib\Core\Repository\DocumentoTipoRepository;
use libredte\lib\Core\Service\DataProviderInterface;

/**
 * Clase que representa un tipo de documento que se puede utilizar.
 *
 * Ya sea tributario (electrónico o no) o informativo (oficial o no).
 */
class DocumentoTipo
{
    /**
     * Código asignado al tipo de documento.
     *
     * Si el código lo asigna el SII es un código oficial.
     *
     * @var int|string
     */
    private int|string $codigo;

    /**
     * Nombre del tipo de documento.
     *
     * @var string
     */
    private string $nombre;

    /**
     * Categoría del documento.
     *
     *   - T: Tributario oficial del SII.
     *   - I: Informativo oficial del SII.
     *   - R: Informativo no oficial del SII.
     *
     * @var string|null
     */
    private ?string $categoria;

    /**
     * Indica si el documento es un documento tributario electrónico.
     *
     * @var bool|null
     */
    private ?bool $electronico;

    /**
     * Indica si el documento se debe enviar al SII.
     *
     * @var bool|null
     */
    private ?bool $enviar;

    /**
     * Indica si el documento puede ser utilizado en las compras.
     *
     * @var bool|null
     */
    private ?bool $compra;

    /**
     * Indica si el documento puede ser utilizado en las ventas.
     *
     * @var bool|null
     */
    private ?bool $venta;

    /**
     * Indica el tipo de operación que el documento registra en los libros.
     *
     *   - S: Suma en el libro.
     *   - R: Resta en el libro.
     *
     * @var string|null
     */
    private ?string $operacion;

    /**
     * Indica si el documento puede ser cedido.
     *
     * @var bool|null
     */
    private ?bool $cedible;

    /**
     * Tag XML del documento que está bajo el tag "DTE".
     *
     *   - `Documento`.
     *   - `Exportaciones`.
     *   - `Liquidacion`.
     *
     * @var string|null
     */
    private ?string $tag_xml;

    /**
     * Indica si el documento está disponible para ser emitido en LibreDTE.
     *
     * @var boolean
     */
    private bool $disponible;

    /**
     * Constructor de la clase.
     *
     * @param int|string $codigo Código del tipo de documento tributario.
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(
        int|string $codigo,
        ?DataProviderInterface $dataProvider = null
    ) {
        $this->codigo = $codigo;

        $repository = new DocumentoTipoRepository($dataProvider);
        $data = $repository->getData($this->codigo);

        foreach ($data as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Getters.
    |--------------------------------------------------------------------------
    */

    /**
     * Entrega el código del documento.
     *
     * @return int|string
     */
    public function getCodigo(): int|string
    {
        return $this->codigo;
    }

    /**
     * Entrega el nombre del documento.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

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
     * @return string|null
     */
    public function getTagXML(): ?string
    {
        return $this->tag_xml;
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos que entregan información a partir del tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si el documento es de tipo boleta.
     *
     * @return boolean
     */
    public function esBoleta(): bool
    {
        return in_array($this->codigo, [39, 41]);
    }

    /**
     * Indica si el documento es de exportación.
     *
     * @return boolean
     */
    public function esExportacion(): bool
    {
        return $this->tag_xml === 'Exportaciones';
    }

    /**
     * Indica si el documento es cedible.
     *
     * @return bool
     */
    public function esCedible(): bool
    {
        return $this->cedible ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos que indican que campos son requeridos según el tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si el documento requiere el tag "TpoTranVenta" en el XML.
     *
     * @return boolean
     */
    public function requiereTpoTranVenta(): bool
    {
        return !in_array($this->codigo, [39, 41, 110, 111, 112]);
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos que proveen valores por defecto según el tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * Entrega el valor por defecto del indicador de servicio para el tipo de
     * documento.
     *
     * @return integer|false
     */
    public function getDefaultIndServicio(): int|false
    {
        return $this->esBoleta() ? 3 : false;
    }

    /**
     * Entrega el valor por defecto de la tasa de IVA.
     *
     * La regla es: documentos exentos y de exportación sin IVA. El resto con
     * el valor vigente del IVA. Actualmente un 19%.
     *
     * @return float|false
     */
    public function getDefaultTasaIVA(): float|false
    {
        $TasaIVA = 19;
        return !in_array($this->codigo, [41, 110, 111, 112]) ? $TasaIVA : false;
    }

    /**
     * Entrega el valor del crédito de IVA para empresas constructoras.
     *
     * @return float
     */
    public function getDefaultCredEC(): float
    {
        return 0.65;
    }
}
