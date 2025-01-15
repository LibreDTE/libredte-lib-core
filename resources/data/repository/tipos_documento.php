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

use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoSobre;

return [
    // Documentos tributarios oficiales del SII.
    29 => [
        'nombre' => 'Factura de inicio',
    ],
    30 => [
        'nombre' => 'Factura',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    32 => [
        'nombre' => 'Factura de venta bienes y servicios no afectos o exentos de IVA',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    CodigoDocumento::FACTURA_AFECTA->value => [
        'nombre' => CodigoDocumento::FACTURA_AFECTA->getNombre(),
        'nombre_corto' => CodigoDocumento::FACTURA_AFECTA->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
        'cedible' => true,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::FACTURA_AFECTA->getAlias(),
        'interface' => CodigoDocumento::FACTURA_AFECTA->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    CodigoDocumento::FACTURA_EXENTA->value => [
        'nombre' => CodigoDocumento::FACTURA_EXENTA->getNombre(),
        'nombre_corto' => CodigoDocumento::FACTURA_EXENTA->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
        'cedible' => true,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::FACTURA_EXENTA->getAlias(),
        'interface' => CodigoDocumento::FACTURA_EXENTA->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    35 => [
        'nombre' => 'Boleta',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => false,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    38 => [
        'nombre' => 'Boleta exenta',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => false,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    CodigoDocumento::BOLETA_AFECTA->value => [
        'nombre' => CodigoDocumento::BOLETA_AFECTA->getNombre(),
        'nombre_corto' => CodigoDocumento::BOLETA_AFECTA->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => false,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::BOLETA_AFECTA->getAlias(),
        'interface' => CodigoDocumento::BOLETA_AFECTA->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_BOLETA,
    ],
    40 => [
        'nombre' => 'Liquidación factura',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
    ],
    CodigoDocumento::BOLETA_EXENTA->value => [
        'nombre' => CodigoDocumento::BOLETA_EXENTA->getNombre(),
        'nombre_corto' => CodigoDocumento::BOLETA_EXENTA->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => false,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::BOLETA_EXENTA->getAlias(),
        'interface' => CodigoDocumento::BOLETA_EXENTA->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_BOLETA,
    ],
    CodigoDocumento::LIQUIDACION_FACTURA->value => [
        'nombre' => CodigoDocumento::LIQUIDACION_FACTURA->getNombre(),
        'nombre_corto' => CodigoDocumento::LIQUIDACION_FACTURA->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'cedible' => true,
        'tag_xml' => TagXmlDocumento::LIQUIDACION,
        'disponible' => false, // No soportado actualmente en LibreDTE.
        'alias' => CodigoDocumento::LIQUIDACION_FACTURA->getAlias(),
        'interface' => CodigoDocumento::LIQUIDACION_FACTURA->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    45 => [
        'nombre' => 'Factura de compra',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::RESTA,
    ],
    CodigoDocumento::FACTURA_COMPRA->value => [
        'nombre' => CodigoDocumento::FACTURA_COMPRA->getNombre(),
        'nombre_corto' => CodigoDocumento::FACTURA_COMPRA->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::RESTA,
        'cedible' => true,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::FACTURA_COMPRA->getAlias(),
        'interface' => CodigoDocumento::FACTURA_COMPRA->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    48 => [
        'nombre' => 'Comprobante de pago electrónico',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => false,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    50 => [
        'nombre' => 'Guía de despacho',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => false,
        'venta' => false,
    ],
    CodigoDocumento::GUIA_DESPACHO->value => [
        'nombre' => CodigoDocumento::GUIA_DESPACHO->getNombre(),
        'nombre_corto' => CodigoDocumento::GUIA_DESPACHO->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => false,
        'venta' => false,
        'cedible' => true,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::GUIA_DESPACHO->getAlias(),
        'interface' => CodigoDocumento::GUIA_DESPACHO->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    55 => [
        'nombre' => 'Nota de débito',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    CodigoDocumento::NOTA_DEBITO->value => [
        'nombre' => CodigoDocumento::NOTA_DEBITO->getNombre(),
        'nombre_corto' => CodigoDocumento::NOTA_DEBITO->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::NOTA_DEBITO->getAlias(),
        'interface' => CodigoDocumento::NOTA_DEBITO->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    60 => [
        'nombre' => 'Nota de crédito',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::RESTA,
    ],
    CodigoDocumento::NOTA_CREDITO->value => [
        'nombre' => CodigoDocumento::NOTA_CREDITO->getNombre(),
        'nombre_corto' => CodigoDocumento::NOTA_CREDITO->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::RESTA,
        'tag_xml' => TagXmlDocumento::DOCUMENTO,
        'disponible' => true,
        'alias' => CodigoDocumento::NOTA_CREDITO->getAlias(),
        'interface' => CodigoDocumento::NOTA_CREDITO->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    101 => [
        'nombre' => 'Factura de exportación',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    102 => [
        'nombre' => 'Factura de venta exenta a zona franca primaria',
    ],
    103 => [
        'nombre' => 'Liquidación',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => false,
        'venta' => true,
    ],
    104 => [
        'nombre' => 'Nota de débito de exportación',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
    ],
    105 => [
        'nombre' => 'Boleta liquidación',
    ],
    106 => [
        'nombre' => 'Nota de crédito de exportación',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::RESTA,
    ],
    108 => [
        'nombre' => 'Solicitud registro de factura (SRF)',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
    ],
    109 => [
        'nombre' => 'Factura turista',
    ],
    CodigoDocumento::FACTURA_EXPORTACION->value => [
        'nombre' => CodigoDocumento::FACTURA_EXPORTACION->getNombre(),
        'nombre_corto' => CodigoDocumento::FACTURA_EXPORTACION->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
        'tag_xml' => TagXmlDocumento::EXPORTACIONES,
        'disponible' => true,
        'alias' => CodigoDocumento::FACTURA_EXPORTACION->getAlias(),
        'interface' => CodigoDocumento::FACTURA_EXPORTACION->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    CodigoDocumento::NOTA_DEBITO_EXPORTACION->value => [
        'nombre' => CodigoDocumento::NOTA_DEBITO_EXPORTACION->getNombre(),
        'nombre_corto' => CodigoDocumento::NOTA_DEBITO_EXPORTACION->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::SUMA,
        'tag_xml' => TagXmlDocumento::EXPORTACIONES,
        'disponible' => true,
        'alias' => CodigoDocumento::NOTA_DEBITO_EXPORTACION->getAlias(),
        'interface' => CodigoDocumento::NOTA_DEBITO_EXPORTACION->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    CodigoDocumento::NOTA_CREDITO_EXPORTACION->value => [
        'nombre' => CodigoDocumento::NOTA_CREDITO_EXPORTACION->getNombre(),
        'nombre_corto' => CodigoDocumento::NOTA_CREDITO_EXPORTACION->getNombreCorto(),
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => OperacionDocumento::RESTA,
        'tag_xml' => TagXmlDocumento::EXPORTACIONES,
        'disponible' => true,
        'alias' => CodigoDocumento::NOTA_CREDITO_EXPORTACION->getAlias(),
        'interface' => CodigoDocumento::NOTA_CREDITO_EXPORTACION->getInterface(),
        'tipo_sobre' => TipoSobre::ENVIO_DTE,
    ],
    // Documentos informativos oficiales del SII.
    801 => [
        'nombre' => 'Orden de compra',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    802 => [
        'nombre' => 'Nota de pedido',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    803 => [
        'nombre' => 'Contrato',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    804 => [
        'nombre' => 'Resolución',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    805 => [
        'nombre' => 'Procedo ChileCompra',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    806 => [
        'nombre' => 'Ficha ChileCompra',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    807 => [
        'nombre' => 'DUS',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    808 => [
        'nombre' => 'Conocimiento de embarque (B/L)',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    809 => [
        'nombre' => 'AWB',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    810 => [
        'nombre' => 'Manifiesto internacional (MIC)',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    811 => [
        'nombre' => 'Carta de porte',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    812 => [
        'nombre' => 'Resolución SNA',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    813 => [
        'nombre' => 'Pasaporte',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    814 => [
        'nombre' => 'Certificado de depósito Bolsa Prod. Chile',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    815 => [
        'nombre' => 'Vale de prenda Bolsa Prod. Chile',
        'categoria' => CategoriaDocumento::INFORMATIVO,
    ],
    // Otros Documentos tributarios oficiales del SII.
    901 => [
        'nombre' => 'Factura de ventas a empresas del territorio preferencial',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => false,
    ],
    902 => [
        'nombre' => 'Conocimiento de embarque',
    ],
    903 => [
        'nombre' => 'Documento único de salida (DUS)',
    ],
    904 => [
        'nombre' => 'Factura de traspaso',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => true,
    ],
    905 => [
        'nombre' => 'Factura de reexpedición',
    ],
    906 => [
        'nombre' => 'Boletas venta módulos ZF (todas)',
    ],
    907 => [
        'nombre' => 'Facturas venta módulo ZF (todas)',
    ],
    909 => [
        'nombre' => 'Facturas venta módulo ZF',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => false,
    ],
    910 => [
        'nombre' => 'Solicitud traslado zona franca (Z)',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => false,
    ],
    911 => [
        'nombre' => 'Declaración de ingreso a zona franca primaria',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => false,
    ],
    914 => [
        'nombre' => 'Declaración de ingreso (DIN)',
        'categoria' => CategoriaDocumento::TRIBUTARIO,
        'electronico' => false,
        'compra' => true,
        'venta' => false,
        'operacion' => OperacionDocumento::SUMA,
    ],
    919 => [
        'nombre' => 'Resumen ventas de nacionales pasajes sin factura',
    ],
    920 => [
        'nombre' => 'Otros registros no documentados (aumenta débito)',
    ],
    922 => [
        'nombre' => 'Otros registros (disminuye débito)',
    ],
    924 => [
        'nombre' => 'Resumen ventas de internacionales pasajes sin factura',
    ],
    // Referencias no oficial del SII (pero comúnmente usados en Chile).
    'HEM' => [
        'nombre' => 'Hoja de entrada de materiales (HEM)',
        'categoria' => CategoriaDocumento::REFERENCIA,
    ],
    'HES' => [
        'nombre' => 'Hoja de entrada de servicios (HES)',
        'categoria' => CategoriaDocumento::REFERENCIA,
    ],
    'EM' => [
        'nombre' => 'Entrada de mercadería (EM)',
        'categoria' => CategoriaDocumento::REFERENCIA,
    ],
    'RDM' => [
        'nombre' => 'Recepción de material/mercadería (RDM)',
        'categoria' => CategoriaDocumento::REFERENCIA,
    ],
    'MLE' => [
        'nombre' => 'Modalidad libre elección (MLE)',
        'categoria' => CategoriaDocumento::REFERENCIA,
    ],
    'RC' => [
        'nombre' => 'Recepción conforme (RC)',
        'categoria' => CategoriaDocumento::REFERENCIA,
    ],
];
