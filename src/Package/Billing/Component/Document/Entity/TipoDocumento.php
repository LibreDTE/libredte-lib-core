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
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;
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
     * @var bool
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
     * Contructor del tipo de documento.
     *
     * @param int|string $codigo
     * @param string $nombre
     * @param string|null $nombre_corto
     */
    public function __construct(
        int|string $codigo,
        string $nombre,
        ?string $nombre_corto = null
    ) {
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->nombre_corto = $nombre_corto;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getCodigo(): int|string
    {
        return $this->codigo;
    }

    /**
     * {@inheritDoc}
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

    /**
     * {@inheritDoc}
     */
    public function getNombreCorto(): string
    {
        return $this->nombre_corto ?? $this->nombre;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoria(): ?CategoriaDocumento
    {
        return $this->categoria;
    }

    /**
     * {@inheritDoc}
     */
    public function esElectronico(): ?bool
    {
        return $this->electronico;
    }

    /**
     * {@inheritDoc}
     */
    public function seEnviaAlSii(): ?bool
    {
        return $this->electronico && $this->enviar;
    }

    /**
     * {@inheritDoc}
     */
    public function disponibleEnCompras(): ?bool
    {
        return $this->compra;
    }

    /**
     * {@inheritDoc}
     */
    public function disponibleEnVentas(): ?bool
    {
        return $this->venta;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperacion(): ?OperacionDocumento
    {
        return $this->operacion;
    }

    /**
     * {@inheritDoc}
     */
    public function esCedible(): bool
    {
        return $this->cedible ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function getTagXml(): ?TagXmlDocumento
    {
        return $this->tag_xml;
    }

    /**
     * {@inheritDoc}
     */
    public function estaDisponible(): bool
    {
        return $this->disponible;
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * {@inheritDoc}
     */
    public function getInterface(): ?string
    {
        return $this->interface;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function esGuiaDespacho(): bool
    {
        return (int) $this->codigo === 52;
    }

    /**
     * {@inheritDoc}
     */
    public function esBoleta(): bool
    {
        return $this->tipo_sobre === TipoSobre::ENVIO_BOLETA;
    }

    /**
     * {@inheritDoc}
     */
    public function esExportacion(): bool
    {
        return $this->tag_xml === TagXmlDocumento::EXPORTACIONES;
    }

    /**
     * {@inheritDoc}
     */
    public function esExento(): bool
    {
        return $this->esExportacion() || in_array($this->codigo, [34, 41]);
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos que indican que campos son requeridos según el tipo de documento.
    |--------------------------------------------------------------------------
    */

    /**
     * {@inheritDoc}
     */
    public function requiereAcuseRecibo(): bool
    {
        // Boletas, notas de crédito y notas de débito no usan acuse de recibo.
        return !in_array($this->codigo, [39, 41, 56, 61, 110, 111, 112]);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getDefaultTasaIVA(): float|false
    {
        $TasaIVA = 19;

        return !in_array($this->codigo, [34, 41, 110, 111, 112]) ? $TasaIVA : false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultCredEC(): float|false
    {
        return !in_array($this->codigo, [39, 41, 46, 110, 111, 112]) ? 0.65 : false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultIndServicio(): int|false
    {
        return $this->esBoleta() ? 3 : false;
    }
}
