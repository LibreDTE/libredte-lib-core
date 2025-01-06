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

namespace libredte\lib\Core\Package\Billing\Component\Document\Entity;

use Derafu\Lib\Core\Package\Prime\Component\Entity\Entity\Entity;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Mapping as DEM;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\TipoDocumentoRepository;

/**
 * Entidad de tipos de documentos tributarios.
 */
#[DEM\Entity(repositoryClass: TipoDocumentoRepository::class)]
class TipoDocumento extends Entity implements TipoDocumentoInterface
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
     * Nombre corto del tipo de documento.
     *
     * @var string|null
     */
    private ?string $nombre_corto;

    /**
     * Categoría del documento.
     *
     *   - T: Tributario oficial del SII.
     *   - I: Informativo oficial del SII.
     *   - R: Informativo no oficial del SII.
     *
     * @var CategoriaDocumento|null
     */
    private ?CategoriaDocumento $categoria = null;

    /**
     * Indica si el documento es un documento tributario electrónico.
     *
     * @var bool|null
     */
    private ?bool $electronico = null;

    /**
     * Indica si el documento se debe enviar al SII.
     *
     * @var bool|null
     */
    private ?bool $enviar = null;

    /**
     * Indica si el documento puede ser utilizado en las compras.
     *
     * @var bool|null
     */
    private ?bool $compra = null;

    /**
     * Indica si el documento puede ser utilizado en las ventas.
     *
     * @var bool|null
     */
    private ?bool $venta = null;

    /**
     * Indica el tipo de operación que el documento registra en los libros.
     *
     *   - S: Suma en el libro.
     *   - R: Resta en el libro.
     *
     * @var OperacionDocumento|null
     */
    private ?OperacionDocumento $operacion = null;

    /**
     * Indica si el documento puede ser cedido.
     *
     * @var bool|null
     */
    private ?bool $cedible = null;

    /**
     * Tag XML del documento que está bajo el tag "DTE".
     *
     *   - `Documento`.
     *   - `Exportaciones`.
     *   - `Liquidacion`.
     *
     * @var TagXmlDocumento|null
     */
    private ?TagXmlDocumento $tag_xml = null;

    /**
     * Indica si el documento está disponible para ser emitido en LibreDTE.
     *
     * @var boolean
     */
    private bool $disponible = false;

    /**
     * Código técnico del documento.
     *
     * Importante: Este es un atributo interno de LibreDTE.
     *
     * @var string|null
     */
    private ?string $alias = null;

    /**
     * Interfaz de la clase que se debe utilizar para construir un documento de
     * este tipo.
     *
     * Importante: Este es un atributo interno de LibreDTE.
     *
     * @var string|null
     */
    private ?string $interface = null;

    /**
     * Tipo del sobre de documentos que se debe utilizar cuando se requiera
     * realizar el envío de este tipo de documento.
     *
     * @var TipoSobre|null
     */
    private ?TipoSobre $tipo_sobre = null;

    /*
    |--------------------------------------------------------------------------
    | Métodos mágicos.
    |--------------------------------------------------------------------------
    */

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->nombre;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters.
    |--------------------------------------------------------------------------
    */

    /**
     * {@inheritdoc}
     */
    public function getCodigo(): int|string
    {
        return $this->codigo;
    }

    /**
     * {@inheritdoc}
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

    /**
     * {@inheritdoc}
     */
    public function getNombreCorto(): string
    {
        return $this->nombre_corto ?? $this->nombre;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoria(): ?CategoriaDocumento
    {
        return $this->categoria;
    }

    /**
     * {@inheritdoc}
     */
    public function esElectronico(): ?bool
    {
        return $this->electronico;
    }

    /**
     * {@inheritdoc}
     */
    public function seEnviaAlSii(): ?bool
    {
        return $this->electronico && $this->enviar;
    }

    /**
     * {@inheritdoc}
     */
    public function disponibleEnCompras(): ?bool
    {
        return $this->compra;
    }

    /**
     * {@inheritdoc}
     */
    public function disponibleEnVentas(): ?bool
    {
        return $this->venta;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperacion(): ?OperacionDocumento
    {
        return $this->operacion;
    }

    /**
     * {@inheritdoc}
     */
    public function esCedible(): bool
    {
        return $this->cedible ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagXml(): ?TagXmlDocumento
    {
        return $this->tag_xml;
    }

    /**
     * {@inheritdoc}
     */
    public function estaDisponible(): bool
    {
        return $this->disponible;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getInterface(): ?string
    {
        return $this->interface;
    }

    /**
     * {@inheritdoc}
     */
    public function getTipoSobre(): ?TipoSobre
    {
        return $this->tipo_sobre;
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos que entregan información a partir del tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * {@inheritdoc}
     */
    public function esBoleta(): bool
    {
        return in_array($this->codigo, [39, 41]);
    }

    /**
     * {@inheritdoc}
     */
    public function esExportacion(): bool
    {
        return $this->tag_xml === 'Exportaciones';
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos que indican que campos son requeridos según el tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getDefaultTasaIVA(): float|false
    {
        $TasaIVA = 19;

        return !in_array($this->codigo, [41, 110, 111, 112]) ? $TasaIVA : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCredEC(): float|false
    {
        return !in_array($this->codigo, [39, 41, 46, 110, 111, 112]) ? 0.65 : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultIndServicio(): int|false
    {
        return $this->esBoleta() ? 3 : false;
    }
}
