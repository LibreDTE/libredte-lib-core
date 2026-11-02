<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy;

use Derafu\Array\ArrayCaster;
use Derafu\Array\ArrayDuplicates;
use Derafu\Array\ArrayPath;
use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Value\ValueParser;
use Derafu\Value\ValueType;
use Derafu\Xml\Contract\XmlEncoderInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroComprasVentasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;

/**
 * Estrategia base de construcción para Libro de Compras/Ventas.
 *
 * Recibe el bag con detalles ya normalizados por el `LoaderWorker`,
 * calcula los totales del período y genera el XML según `LibroCV_v10.xsd`.
 */
abstract class AbstractLibroComprasVentasBuilderStrategy extends AbstractStrategy
{
    /**
     * Tipos de documento que se pueden informar solo con su resumen: boletas,
     * boletas exentas y pagos electrónicos.
     *
     * @var array<int, int>
     */
    public const TIPOS_RESUMEN_MANUAL = [35, 38, 48];

    /**
     * Dato de la carátula que exige cada tipo de libro.
     *
     * El esquema deja estos campos como opcionales: que sean obligatorios para
     * estos tipos de libro no lo valida el esquema, por eso se valida al
     * construir el libro.
     *
     * @var array<string, string>
     */
    public const CARATULA_REQUERIDA = [
        'ESPECIAL' => 'FolioNotificacion',
        'RECTIFICA' => 'CodAutRec',
    ];

    /**
     * Valores por defecto de los totales del período por tipo de documento.
     *
     * El orden de las claves determina el orden de los elementos en el XML.
     *
     * @var array<string, mixed>
     */
    private array $totalDefault = [
        'TpoDoc' => null,
        'TotDoc' => 0,
        'TotAnulado' => false,
        'TotOpExe' => false,
        'TotMntExe' => 0,
        'TotMntNeto' => 0,
        'TotMntIVA' => 0,
        'TotIVAPropio' => false,
        'TotIVATerceros' => false,
        'TotLey18211' => false,
        'TotMntActivoFijo' => false,
        'TotMntIVAActivoFijo' => false,
        'TotIVANoRec' => false,
        'TotIVAUsoComun' => false,
        'FctProp' => false,
        'TotCredIVAUsoComun' => false,
        'TotIVAFueraPlazo' => false,
        'TotOtrosImp' => false,
        'TotIVARetTotal' => false,
        'TotIVARetParcial' => false,
        'TotImpSinCredito' => false,
        'TotMntTotal' => 0,
        'TotIVANoRetenido' => false,
        'TotMntNoFact' => false,
        'TotMntPeriodo' => false,
        'TotPsjNac' => false,
        'TotPsjInt' => false,
        'TotTabPuros' => false,
        'TotTabCigarrillos' => false,
        'TotTabElaborado' => false,
        'TotImpVehiculo' => false,
    ];

    public function __construct(
        private XmlEncoderInterface $xmlEncoder
    ) {
    }

    /**
     * Construye el Libro de Compras/Ventas a partir del bag normalizado.
     */
    public function build(BookBagInterface $bag): LibroComprasVentasInterface
    {
        // Opciones de construcción.
        $simplificado = (bool) ($bag->getBuilderOptions()['simplificado'] ?? false);
        $detalle = (bool) ($bag->getBuilderOptions()['detalle'] ?? true);

        // Datos del bag.
        $caratula = $bag->getCaratula();
        $detalles = $bag->getDetalle();

        // Ningún documento puede estar repetido en el detalle: el esquema no lo
        // valida.
        ArrayDuplicates::assertUnique(
            $detalles,
            $bag->getTipo()->getDocumentKey(),
            ValueParser::parse(...)
        );

        // Calcular resumen del período.
        $totalesPeriodo = $this->calculateTotalesPeriodo($detalles);
        $totalesPeriodo = $this->addResumen($totalesPeriodo, $bag->getResumen());
        $resumenPeriodo = $totalesPeriodo ? ['TotalesPeriodo' => $totalesPeriodo] : false;

        // Se actualizan los detalles por si se modificó algo al realizar el
        // cálculo de totales. Por ejemplo, si se quitó el factor de
        // proporcionalidad de IVA de uso común, campo FctProp.
        $bag->setDetalle($detalles);

        // Construir carátula normalizada.
        $tipoOper = strtoupper(ArrayReader::fromArray(ArrayCaster::nullify($caratula, [false, '']))->getAsString('TipoOperacion') ?? 'VENTA');
        $caratulaNorm = array_merge([
            'RutEmisorLibro' => false,
            'RutEnvia' => false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => false,
            'NroResol' => false,
            'TipoOperacion' => 'VENTA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
            'FolioNotificacion' => false,
        ], $caratula);

        // Un libro especial lleva el folio de la notificación y uno de
        // rectificación el código de autorización.
        $caratulaReader = ArrayReader::fromArray(ArrayCaster::nullify($caratulaNorm, [false, '']));
        $tipoLibro = $caratulaReader->getAsString('TipoLibro') ?? '';
        $campo = self::CARATULA_REQUERIDA[$tipoLibro] ?? null;
        if ($campo !== null && in_array($caratulaNorm[$campo] ?? null, [null, false, ''], true)) {
            throw new BookException([
                'The cover of a book of type {type} must have {field}.',
                'type' => $tipoLibro,
                'field' => $campo,
            ]);
        }

        // Construir ID del documento.
        $rut = str_replace('-', '', $caratulaReader->getAsString('RutEmisorLibro') ?? '');
        $periodo = str_replace('-', '', $caratulaReader->getAsString('PeriodoTributario') ?? date('Y-m'));
        $id = sprintf('LibreDTE_LIBRO_%s_%s_%s_%s', $tipoOper, $rut, $periodo, time());

        // Generar estructura XML.
        $xmlDocument = $this->xmlEncoder->encode([
            'LibroCompraVenta' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => $simplificado
                        ? 'http://www.sii.cl/SiiDte LibroCVS_v10.xsd'
                        : 'http://www.sii.cl/SiiDte LibroCV_v10.xsd'
                    ,
                    'version' => '1.0',
                ],
                'EnvioLibro' => [
                    '@attributes' => ['ID' => $id],
                    'Caratula' => $caratulaNorm,
                    'ResumenPeriodo' => $resumenPeriodo,
                    'Detalle' => $detalle ? ($this->filterDetalle($detalles) ?: false) : false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ],
        ]);

        return new LibroComprasVentas($xmlDocument);
    }

    /**
     * Entrega las filas del detalle que van en el XML del libro.
     *
     * Por defecto son todas. Una estrategia puede dejar fuera algunas: sus
     * totales igual se cuentan en el resumen del período.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return array<int, array<string, mixed>>
     */
    protected function filterDetalle(array $detalles): array
    {
        return $detalles;
    }

    /**
     * Agrega al resumen del período el resumen manual: los totales de
     * documentos que no están en el detalle.
     *
     * @param array<int, array<string, mixed>> $totales Totales calculados
     * desde el detalle, por tipo de documento.
     * @param array<int, array<string, mixed>> $resumen Resumen manual.
     * @return array<int, array<string, mixed>>
     * @throws BookException Si un elemento no tiene `TpoDoc` y `TotDoc`, si su
     * tipo de documento no es uno de `TIPOS_RESUMEN_MANUAL`, si el tipo se repite
     * en el resumen o si el tipo ya está en el detalle.
     */
    private function addResumen(array $totales, array $resumen): array
    {
        if ($resumen === []) {
            return $totales;
        }

        $manual = [];
        $number = 0;
        foreach ($resumen as $total) {
            $number++;
            if (!isset($total['TpoDoc']) || !isset($total['TotDoc'])) {
                throw new BookException([
                    'Element {number} of the manual summary must have TpoDoc and TotDoc.',
                    'number' => $number,
                ]);
            }

            // Un texto sigue siendo texto y no es uno de los tipos permitidos.
            $tpoDoc = ValueParser::parse($total['TpoDoc']);
            if (!in_array($tpoDoc, self::TIPOS_RESUMEN_MANUAL, true)) {
                throw new BookException([
                    'The document type {type} can not have a manual summary: it must be one of {allowed}.',
                    'type' => $total['TpoDoc'],
                    'allowed' => implode(', ', self::TIPOS_RESUMEN_MANUAL),
                ]);
            }

            if (isset($manual[$tpoDoc])) {
                throw new BookException([
                    'The document type {type} is repeated in the manual summary.',
                    'type' => $tpoDoc,
                ]);
            }

            if (isset($totales[$tpoDoc])) {
                throw new BookException([
                    'The document type {type} is in the detail of the book, so it can not have a manual summary.',
                    'type' => $tpoDoc,
                ]);
            }

            $manual[$tpoDoc] = array_merge($this->totalDefault, $total, ['TpoDoc' => $tpoDoc]);
        }

        $totales += $manual;
        ksort($totales);

        return $totales;
    }

    /**
     * Calcula los totales del período agrupados por TpoDoc.
     *
     * Se quita el factor de proporcionalidad (`FctProp`) de los detalles, que no
     * es parte del XML de un detalle.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return array<int, array<string, mixed>>
     */
    private function calculateTotalesPeriodo(array &$detalles): array
    {
        $groups = [];

        $listType = ValueType::listOf(ValueType::array());

        foreach ($detalles as &$d) {
            // IVA no recuperable y otros impuestos, como lista de impuestos.
            foreach (['IVANoRec', 'OtrosImp'] as $field) {
                if (!empty($d[$field])) {
                    $d = ArrayPath::wrap($d, $field);
                }
            }

            $row = ArrayReader::fromArray(ArrayCaster::nullify($d, [false, '']));

            $tpoDoc = $row->getAsInt('TpoDoc');
            if ($tpoDoc === null || $tpoDoc === 0) {
                continue;
            }

            $groups[$tpoDoc] ??= $this->newGroup();
            $group = &$groups[$tpoDoc];

            $group['documents']++;

            // Documentos anulados solo incrementan el contador.
            if (!empty($d['Anulado']) && $d['Anulado'] === 'A') {
                $group['annulled'] = ($group['annulled'] ?? 0) + 1;
                unset($group);
                continue;
            }

            // Montos base.
            $group['exempt'] += $row->getAsInt('MntExe') ?? 0;
            $group['net'] += $row->getAsInt('MntNeto') ?? 0;
            $group['vat'] += $row->getAsInt('MntIVA') ?? 0;
            $group['total'] += $row->getAsInt('MntTotal') ?? 0;

            // Activo fijo.
            $fixedAsset = $row->getAsInt('MntActivoFijo') ?? 0;
            if ($fixedAsset !== 0) {
                $group['fixedAsset'] = ($group['fixedAsset'] ?? 0) + $fixedAsset;
            }
            $fixedAssetVat = $row->getAsInt('MntIVAActivoFijo') ?? 0;
            if ($fixedAssetVat !== 0) {
                $group['fixedAssetVat'] = ($group['fixedAssetVat'] ?? 0) + $fixedAssetVat;
            }

            // IVA no recuperable.
            foreach ($row->get('IVANoRec', $listType) ?? [] as $iva) {
                $item = ArrayReader::fromArray($iva);
                $code = $item->getAsInt('CodIVANoRec') ?? 0;
                $group['notRecoverable'][$code] ??= ['code' => $code, 'operations' => 0, 'amount' => 0];
                $group['notRecoverable'][$code]['operations']++;
                $group['notRecoverable'][$code]['amount'] += $item->getAsInt('MntIVANoRec') ?? 0;
            }

            // IVA de uso común.
            if (isset($d['FctProp'])) {
                $proportion = $row->getAsNumber('FctProp') ?? 0;
                if ($proportion != 0) {
                    $commonVat = $row->getAsInt('IVAUsoComun') ?? 0;
                    $group['commonVat'] = ($group['commonVat'] ?? 0) + $commonVat;
                    $group['proportion'] = $proportion / 100;
                    $group['commonCredit'] = ($group['commonCredit'] ?? 0) + (int) round($commonVat * ($proportion / 100));
                }
                unset($d['FctProp']);
            }

            // IVA fuera de plazo.
            $latePayment = $row->getAsInt('IVAFueraPlazo') ?? 0;
            if ($latePayment !== 0) {
                $group['latePayment'] = ($group['latePayment'] ?? 0) + $latePayment;
            }

            // Otros impuestos.
            foreach ($row->get('OtrosImp', $listType) ?? [] as $imp) {
                $item = ArrayReader::fromArray($imp);
                $code = $item->getAsInt('CodImp') ?? 0;
                $group['otherTaxes'][$code] ??= ['code' => $code, 'amount' => 0];
                $group['otherTaxes'][$code]['amount'] += $item->getAsInt('MntImp') ?? 0;
            }

            // Monto sin crédito.
            $withoutCredit = $row->getAsInt('MntSinCred') ?? 0;
            if ($withoutCredit !== 0) {
                $group['withoutCredit'] = ($group['withoutCredit'] ?? 0) + $withoutCredit;
            }

            // IVA retenido.
            $withheldTotal = $row->getAsInt('IVARetTotal') ?? 0;
            if ($withheldTotal !== 0) {
                $group['withheldTotal'] = ($group['withheldTotal'] ?? 0) + $withheldTotal;
            }
            $withheldPartial = $row->getAsInt('IVARetParcial') ?? 0;
            if ($withheldPartial !== 0) {
                $group['withheldPartial'] = ($group['withheldPartial'] ?? 0) + $withheldPartial;
            }
            $notWithheld = $row->getAsInt('IVANoRetenido') ?? 0;
            if ($notWithheld !== 0) {
                $group['notWithheld'] = ($group['notWithheld'] ?? 0) + $notWithheld;
            }

            // Impuesto vehículos.
            $vehicle = $row->getAsInt('ImpVehiculo') ?? 0;
            if ($vehicle !== 0) {
                $group['vehicle'] = ($group['vehicle'] ?? 0) + $vehicle;
            }

            unset($group);
        }
        unset($d);

        // Ordenar por tipo de documento ascendente.
        ksort($groups);

        $totales = [];
        foreach ($groups as $tpoDoc => $group) {
            $totales[$tpoDoc] = array_merge($this->totalDefault, [
                'TpoDoc' => $tpoDoc,
                'TotDoc' => $group['documents'],
                'TotAnulado' => $group['annulled'] ?? false,
                'TotMntExe' => $group['exempt'],
                'TotMntNeto' => $group['net'],
                'TotMntIVA' => $group['vat'],
                'TotMntActivoFijo' => $group['fixedAsset'] ?? false,
                'TotMntIVAActivoFijo' => $group['fixedAssetVat'] ?? false,
                'TotIVANoRec' => $group['notRecoverable'] === [] ? false : array_values(array_map(
                    static fn (array $iva): array => [
                        'CodIVANoRec' => $iva['code'],
                        'TotOpIVANoRec' => $iva['operations'],
                        'TotMntIVANoRec' => $iva['amount'],
                    ],
                    $group['notRecoverable']
                )),
                'TotIVAUsoComun' => $group['commonVat'] ?? false,
                'FctProp' => $group['proportion'] ?? false,
                'TotCredIVAUsoComun' => $group['commonCredit'] ?? false,
                'TotIVAFueraPlazo' => $group['latePayment'] ?? false,
                'TotOtrosImp' => $group['otherTaxes'] === [] ? false : array_values(array_map(
                    static fn (array $imp): array => ['CodImp' => $imp['code'], 'TotMntImp' => $imp['amount']],
                    $group['otherTaxes']
                )),
                'TotIVARetTotal' => $group['withheldTotal'] ?? false,
                'TotIVARetParcial' => $group['withheldPartial'] ?? false,
                'TotImpSinCredito' => $group['withoutCredit'] ?? false,
                'TotMntTotal' => $group['total'],
                'TotIVANoRetenido' => $group['notWithheld'] ?? false,
                'TotImpVehiculo' => $group['vehicle'] ?? false,
            ]);
        }

        return $totales;
    }

    /**
     * Entrega los acumuladores de un tipo de documento, vacíos. Un total que es
     * `null` es uno que ningún documento aportó: queda como `false` en el XML.
     *
     * @return array{
     *     documents: int,
     *     annulled: int|null,
     *     exempt: int,
     *     net: int,
     *     vat: int,
     *     total: int,
     *     fixedAsset: int|null,
     *     fixedAssetVat: int|null,
     *     notRecoverable: array<int, array{code: int, operations: int, amount: int}>,
     *     commonVat: int|null,
     *     proportion: int|float|null,
     *     commonCredit: int|null,
     *     latePayment: int|null,
     *     otherTaxes: array<int, array{code: int, amount: int}>,
     *     withoutCredit: int|null,
     *     withheldTotal: int|null,
     *     withheldPartial: int|null,
     *     notWithheld: int|null,
     *     vehicle: int|null
     * }
     */
    private function newGroup(): array
    {
        return [
            'documents' => 0,
            'annulled' => null,
            'exempt' => 0,
            'net' => 0,
            'vat' => 0,
            'total' => 0,
            'fixedAsset' => null,
            'fixedAssetVat' => null,
            'notRecoverable' => [],
            'commonVat' => null,
            'proportion' => null,
            'commonCredit' => null,
            'latePayment' => null,
            'otherTaxes' => [],
            'withoutCredit' => null,
            'withheldTotal' => null,
            'withheldPartial' => null,
            'notWithheld' => null,
            'vehicle' => null,
        ];
    }
}
