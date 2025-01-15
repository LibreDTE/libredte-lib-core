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

namespace libredte\lib\Core\Package\Billing\Component\Document\Enum;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\BoletaAfectaInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\BoletaExentaInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\FacturaAfectaInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\FacturaCompraInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\FacturaExentaInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\FacturaExportacionInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\GuiaDespachoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\LiquidacionFacturaInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\NotaCreditoExportacionInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\NotaCreditoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\NotaDebitoExportacionInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Document\NotaDebitoInterface;

/**
 * Enum del código de los documentos tributarios electrónicos.
 */
enum CodigoDocumento: int
{
    case FACTURA_AFECTA = 33;
    case FACTURA_EXENTA = 34;
    case BOLETA_AFECTA = 39;
    case BOLETA_EXENTA = 41;
    case LIQUIDACION_FACTURA = 43;
    case FACTURA_COMPRA = 46;
    case GUIA_DESPACHO = 52;
    case NOTA_DEBITO = 56;
    case NOTA_CREDITO = 61;
    case FACTURA_EXPORTACION = 110;
    case NOTA_DEBITO_EXPORTACION = 111;
    case NOTA_CREDITO_EXPORTACION = 112;

    /**
     * Mapa con los códigos de documento y su nombre.
     */
    private const NOMBRES = [
        self::FACTURA_AFECTA->value => 'Factura electrónica',
        self::FACTURA_EXENTA->value => 'Factura no afecta o exenta electrónica',
        self::BOLETA_AFECTA->value => 'Boleta electrónica',
        self::BOLETA_EXENTA->value => 'Boleta no afecta o exenta electrónica',
        self::LIQUIDACION_FACTURA->value => 'Liquidación factura electrónica',
        self::FACTURA_COMPRA->value => 'Factura de compra electrónica',
        self::GUIA_DESPACHO->value => 'Guía de despacho electrónica',
        self::NOTA_DEBITO->value => 'Nota de débito electrónica',
        self::NOTA_CREDITO->value => 'Nota de crédito electrónica',
        self::FACTURA_EXPORTACION->value => 'Factura de exportación electrónica',
        self::NOTA_DEBITO_EXPORTACION->value => 'Nota de débito de exportación electrónica',
        self::NOTA_CREDITO_EXPORTACION->value => 'Nota de crédito de exportación electrónica',
    ];

    /**
     * Mapa con los códigos de documento y su alias.
     */
    private const ALIASES = [
        self::FACTURA_AFECTA->value => 'factura_afecta',
        self::FACTURA_EXENTA->value => 'factura_exenta',
        self::BOLETA_AFECTA->value => 'boleta_afecta',
        self::BOLETA_EXENTA->value => 'boleta_exenta',
        self::LIQUIDACION_FACTURA->value => 'liquidacion_factura',
        self::FACTURA_COMPRA->value => 'factura_compra',
        self::GUIA_DESPACHO->value => 'guia_despacho',
        self::NOTA_DEBITO->value => 'nota_debito',
        self::NOTA_CREDITO->value => 'nota_credito',
        self::FACTURA_EXPORTACION->value => 'factura_exportacion',
        self::NOTA_DEBITO_EXPORTACION->value => 'nota_debito_exportacion',
        self::NOTA_CREDITO_EXPORTACION->value => 'nota_credito_exportacion',
    ];

    /**
     * Mapa con los códigos de documento y su interfaz.
     */
    private const INTERFACES = [
        self::FACTURA_AFECTA->value => FacturaAfectaInterface::class,
        self::FACTURA_EXENTA->value => FacturaExentaInterface::class,
        self::BOLETA_AFECTA->value => BoletaAfectaInterface::class,
        self::BOLETA_EXENTA->value => BoletaExentaInterface::class,
        self::LIQUIDACION_FACTURA->value => LiquidacionFacturaInterface::class,
        self::FACTURA_COMPRA->value => FacturaCompraInterface::class,
        self::GUIA_DESPACHO->value => GuiaDespachoInterface::class,
        self::NOTA_DEBITO->value => NotaDebitoInterface::class,
        self::NOTA_CREDITO->value => NotaCreditoInterface::class,
        self::FACTURA_EXPORTACION->value => FacturaExportacionInterface::class,
        self::NOTA_DEBITO_EXPORTACION->value => NotaDebitoExportacionInterface::class,
        self::NOTA_CREDITO_EXPORTACION->value => NotaCreditoExportacionInterface::class,
    ];

    /**
     * Entrega el código del documento oficial del SII.
     *
     * @return int
     */
    public function getCodigo(): int
    {
        return $this->value;
    }

    /**
     * Entrega el nombre del tipo de documento.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return self::NOMBRES[$this->value];
    }

    /**
     * Entrega el nombre corto del tipo de documento.
     *
     * @return string
     */
    public function getNombreCorto(): string
    {
        return str_replace(' electrónica', '', self::NOMBRES[$this->value]);
    }

    /**
     * Entrega el alias del documento.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return self::ALIASES[$this->value];
    }

    /**
     * Entrega la interfaz PHP asociada al tipo de documento.
     *
     * @return string
     */
    public function getInterface(): string
    {
        return self::INTERFACES[$this->value];
    }
}
