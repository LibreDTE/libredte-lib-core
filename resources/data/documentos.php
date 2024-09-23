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

return [
    // Documentos tributarios oficiales del SII.
    29 => [
        'nombre' => 'Factura de inicio',
    ],
    30 => [
        'nombre' => 'Factura',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
    ],
    32 => [
        'nombre' => 'Factura de venta bienes y servicios no afectos o exentos de IVA',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
    ],
    33 => [
        'nombre' => 'Factura electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
        'cedible' => true,
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    34 => [
        'nombre' => 'Factura no afecta o exenta electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
        'cedible' => true,
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    35 => [
        'nombre' => 'Boleta',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => false,
        'venta' => true,
        'operacion' => 'S',
    ],
    38 => [
        'nombre' => 'Boleta exenta',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => false,
        'venta' => true,
        'operacion' => 'S',
    ],
    39 => [
        'nombre' => 'Boleta electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => false,
        'venta' => true,
        'operacion' => 'S',
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    40 => [
        'nombre' => 'Liquidación factura',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
    ],
    41 => [
        'nombre' => 'Boleta no afecta o exenta electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => false,
        'venta' => true,
        'operacion' => 'S',
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    43 => [
        'nombre' => 'Liquidación factura electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'cedible' => true,
        'tag_xml' => 'Liquidacion',
        //'disponible' => true,
    ],
    45 => [
        'nombre' => 'Factura de compra',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'R',
    ],
    46 => [
        'nombre' => 'Factura de compra electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'R',
        'cedible' => true,
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    48 => [
        'nombre' => 'Comprobante de pago electrónico',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => false,
        'venta' => true,
        'operacion' => 'S',
    ],
    50 => [
        'nombre' => 'Guía de despacho',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => false,
        'venta' => false,
    ],
    52 => [
        'nombre' => 'Guía de despacho electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => false,
        'venta' => false,
        'cedible' => true,
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    55 => [
        'nombre' => 'Nota de débito',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
    ],
    56 => [
        'nombre' => 'Nota de débito electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    60 => [
        'nombre' => 'Nota de crédito',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'R',
    ],
    61 => [
        'nombre' => 'Nota de crédito electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'R',
        'tag_xml' => 'Documento',
        'disponible' => true,
    ],
    101 => [
        'nombre' => 'Factura de exportación',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
    ],
    102 => [
        'nombre' => 'Factura de venta exenta a zona franca primaria',
    ],
    103 => [
        'nombre' => 'Liquidación',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => false,
        'venta' => true,
    ],
    104 => [
        'nombre' => 'Nota de débito de exportación',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
    ],
    105 => [
        'nombre' => 'Boleta liquidación',
    ],
    106 => [
        'nombre' => 'Nota de crédito de exportación',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
        'operacion' => 'R',
    ],
    108 => [
        'nombre' => 'Solicitud registro de factura (SRF)',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => true,
    ],
    109 => [
        'nombre' => 'Factura turista',
    ],
    110 => [
        'nombre' => 'Factura de exportación electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
        'tag_xml' => 'Exportaciones',
        'disponible' => true,
    ],
    111 => [
        'nombre' => 'Nota de débito de exportación electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'S',
        'tag_xml' => 'Exportaciones',
        'disponible' => true,
    ],
    112 => [
        'nombre' => 'Nota de crédito de exportación electrónica',
        'categoria' => 'T',
        'electronico' => true,
        'enviar' => true,
        'compra' => true,
        'venta' => true,
        'operacion' => 'R',
        'tag_xml' => 'Exportaciones',
        'disponible' => true,
    ],
    // Documentos informativos oficiales del SII.
    801 => [
        'nombre' => 'Orden de compra',
        'categoria' => 'I',
    ],
    802 => [
        'nombre' => 'Nota de pedido',
        'categoria' => 'I',
    ],
    803 => [
        'nombre' => 'Contrato',
        'categoria' => 'I',
    ],
    804 => [
        'nombre' => 'Resolución',
        'categoria' => 'I',
    ],
    805 => [
        'nombre' => 'Procedo ChileCompra',
        'categoria' => 'I',
    ],
    806 => [
        'nombre' => 'Ficha ChileCompra',
        'categoria' => 'I',
    ],
    807 => [
        'nombre' => 'DUS',
        'categoria' => 'I',
    ],
    808 => [
        'nombre' => 'Conocimiento de embarque (B/L)',
        'categoria' => 'I',
    ],
    809 => [
        'nombre' => 'AWB',
        'categoria' => 'I',
    ],
    810 => [
        'nombre' => 'Manifiesto internacional (MIC)',
        'categoria' => 'I',
    ],
    811 => [
        'nombre' => 'Carta de porte',
        'categoria' => 'I',
    ],
    812 => [
        'nombre' => 'Resolución SNA',
        'categoria' => 'I',
    ],
    813 => [
        'nombre' => 'Pasaporte',
        'categoria' => 'I',
    ],
    814 => [
        'nombre' => 'Certificado de depósito Bolsa Prod. Chile',
        'categoria' => 'I',
    ],
    815 => [
        'nombre' => 'Vale de prenda Bolsa Prod. Chile',
        'categoria' => 'I',
    ],
    // Otros Documentos tributarios oficiales del SII.
    901 => [
        'nombre' => 'Factura de ventas a empresas del territorio preferencial',
        'categoria' => 'T',
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
        'categoria' => 'T',
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
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => false,
    ],
    910 => [
        'nombre' => 'Solicitud traslado zona franca (Z)',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => false,
    ],
    911 => [
        'nombre' => 'Declaración de ingreso a zona franca primaria',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => false,
    ],
    914 => [
        'nombre' => 'Declaración de ingreso (DIN)',
        'categoria' => 'T',
        'electronico' => false,
        'compra' => true,
        'venta' => false,
        'operacion' => 'S',
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
        'categoria' => 'R',
    ],
    'HES' => [
        'nombre' => 'Hoja de entrada de servicios (HES)',
        'categoria' => 'R',
    ],
    'EM' => [
        'nombre' => 'Entrada de mercadería (EM)',
        'categoria' => 'R',
    ],
    'RDM' => [
        'nombre' => 'Recepción de material/mercadería (RDM)',
        'categoria' => 'R',
    ],
    'MLE' => [
        'nombre' => 'Modalidad libre elección (MLE)',
        'categoria' => 'R',
    ],
    'RC' => [
        'nombre' => 'Recepción conforme (RC)',
        'categoria' => 'R',
    ],
];
