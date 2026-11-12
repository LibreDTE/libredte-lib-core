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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy;

use Derafu\Array\ArrayCaster;
use Derafu\Array\ArrayPath;
use Derafu\Array\ArrayReader;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoOperacion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;

/**
 * Estrategia base de carga desde array para Libro de Compras/Ventas.
 *
 * Normaliza cada registro de detalle según el esquema `LibroCV_v10.xsd`:
 * añade valores por defecto, calcula IVA cuando falta, normaliza estructuras
 * de IVA no recuperable y otros impuestos, y calcula el monto total.
 */
abstract class AbstractLibroComprasVentasArrayLoaderStrategy extends AbstractArrayLoaderStrategy
{
    /**
     * Campos del detalle que son montos: números enteros.
     *
     * @var array<int, string>
     */
    private const AMOUNTS = [
        'MntExe', 'MntNeto', 'MntIVA', 'MntActivoFijo', 'MntIVAActivoFijo', 'IVAUsoComun', 'IVAFueraPlazo',
        'IVAPropio', 'IVATerceros', 'Ley18211', 'MntSinCred', 'IVARetTotal', 'IVARetParcial', 'CredEC',
        'DepEnvase', 'MntTotal', 'IVANoRetenido', 'MntNoFact', 'MntPeriodo', 'PsjNac', 'PsjInt', 'TabPuros',
        'TabCigarrillos', 'TabElaborado', 'ImpVehiculo',
    ];

    /**
     * Campos del detalle que son tasas o factores: números.
     *
     * @var array<int, string>
     */
    private const RATES = ['TasaImp', 'FctProp'];

    public function __construct(
        private readonly RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Normaliza la carátula del libro de compra/venta.
     *
     * @param BookBagInterface $bag
     * @return array<string, mixed>
     */
    protected function normalizeCaratula(BookBagInterface $bag): array
    {
        return array_merge([
            'RutEmisorLibro'    => $bag->getEmisor()?->getRut(),
            'RutEnvia'          => $bag->getCertificate()?->getId(),
            'PeriodoTributario' => date('Y-m'),
            'FchResol'          => $bag->getBookAuth()['fecha_resolucion'] ?? null,
            'NroResol'          => $bag->getBookAuth()['numero_resolucion'] ?? null,
            'TipoOperacion'     => $bag->getTipo() === TipoLibro::VENTAS
                ? TipoOperacion::VENTA->value
                : TipoOperacion::COMPRA->value
            ,
            'TipoLibro'         => 'MENSUAL',
            'TipoEnvio'         => 'TOTAL',
            'FolioNotificacion' => null,
        ], $bag->getCaratula());
    }

    /**
     * {@inheritDoc}
     *
     * Normaliza un registro de detalle del libro de compra/venta.
     *
     * El orden de las claves determina el orden de los elementos en el XML,
     * que debe respetar el esquema `LibroCV_v10.xsd`.
     *
     * Los montos son números enteros y las tasas son números: el texto que llega
     * de un CSV se acepta si se puede convertir, y uno que no es un número es un
     * error que dice en qué documento y en qué campo está. Los valores del
     * detalle no se reemplazan por los convertidos.
     *
     * @param array<string, mixed> $detalle
     * @return array<string, mixed>
     */
    protected function normalizeDetalle(array $detalle): array
    {
        // Valores por defecto.
        $default = [
            'TpoDoc' => null,
            'Emisor' => null,
            'IndFactCompra' => null,
            'NroDoc' => null,
            'Anulado' => null,
            'Operacion' => null,
            'TpoImp' => 1,
            'TasaImp' => null,
            'NumInt' => null,
            'IndServicio' => null,
            'IndSinCosto' => null,
            'FchDoc' => null,
            'CdgSIISucur' => null,
            'RUTDoc' => null,
            'RznSoc' => null,
            'Extranjero' => null,
            'TpoDocRef' => null,
            'FolioDocRef' => null,
            'MntExe' => null,
            'MntNeto' => null,
            'MntIVA' => null,
            'MntActivoFijo' => null,
            'MntIVAActivoFijo' => null,
            'IVANoRec' => null,
            'IVAUsoComun' => null,
            'IVAFueraPlazo' => null,
            'IVAPropio' => null,
            'IVATerceros' => null,
            'Ley18211' => null,
            'OtrosImp' => null,
            'MntSinCred' => null,
            'IVARetTotal' => null,
            'IVARetParcial' => null,
            'CredEC' => null,
            'DepEnvase' => null,
            'Liquidaciones' => null,
            'MntTotal' => null,
            'IVANoRetenido' => null,
            'MntNoFact' => null,
            'MntPeriodo' => null,
            'PsjNac' => null,
            'PsjInt' => null,
            'TabPuros' => null,
            'TabCigarrillos' => null,
            'TabElaborado' => null,
            'ImpVehiculo' => null,
        ];

        // Normalizar los campos del detalle.
        $detalle = array_merge($default, ArrayCaster::nullify($detalle, [false]));

        // Si está anulado, se mantiene solo el mínimo requerido.
        if (!empty($detalle['Anulado']) && $detalle['Anulado'] === 'A') {
            return array_merge($default, [
                'TpoDoc' => $detalle['TpoDoc'],
                'NroDoc' => $detalle['NroDoc'],
                'Anulado' => $detalle['Anulado'],
            ]);
        }

        // Los montos y las tasas deben ser números.
        $this->assertNumbers($detalle, self::AMOUNTS, self::RATES);

        // Truncar razón social.
        if ($detalle['RznSoc']) {
            $detalle['RznSoc'] = mb_substr(ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']))->getAsString('RznSoc') ?? '', 0, 50);
        }

        // Calcular IVA de uso común si hay factor de proporcionalidad.
        if (isset($detalle['FctProp'])) {
            if ($detalle['IVAUsoComun'] === null) {
                $detalle['IVAUsoComun'] = $this->percentOfNet($detalle);
            }
        } elseif ($detalle['MntIVA'] === null && !is_array($detalle['IVANoRec']) && $detalle['IVAUsoComun'] === null && $detalle['TasaImp'] && $detalle['MntNeto']) {
            // Calcular IVA si falta y no hay IVA no recuperable.
            $detalle['MntIVA'] = $this->percentOfNet($detalle);
        }

        // Normalizar IVA no recuperable y otros impuestos a lista.
        foreach (['IVANoRec', 'OtrosImp'] as $field) {
            if (!empty($detalle[$field])) {
                $detalle = ArrayPath::wrap($detalle, $field);
            }
        }

        // Los montos y las tasas de los impuestos que van en listas.
        $listType = ValueType::listOf(ValueType::array());
        $impuestos = ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']));
        $ivaNoRec = $impuestos->get('IVANoRec', $listType) ?? [];
        $otrosImp = $impuestos->get('OtrosImp', $listType) ?? [];
        foreach ($ivaNoRec as $item) {
            $this->assertNumbers(array_merge(['TpoDoc' => $detalle['TpoDoc'], 'NroDoc' => $detalle['NroDoc']], $item), ['MntIVANoRec'], []);
        }
        foreach ($otrosImp as $item) {
            $this->assertNumbers(array_merge(['TpoDoc' => $detalle['TpoDoc'], 'NroDoc' => $detalle['NroDoc']], $item), ['MntImp'], ['TasaImp']);
        }

        // Determinar si hay IVA retenido y si es total o parcial.
        $ivaRetenido = $this->calcularIvaRetenidoDetalle($detalle);
        if ($ivaRetenido) {
            // Si el IVA retenido es total.
            if ($ivaRetenido == $detalle['MntIVA']) {
                $detalle['IVARetTotal'] = $ivaRetenido;
            }
            // Si el IVA retenido es parcial.
            else {
                $detalle['IVARetParcial'] = $ivaRetenido;
                $detalle['IVANoRetenido'] = $impuestos->getAsInt('MntIVA', 0) - $ivaRetenido;
            }
        }

        // Calcular monto total si falta.
        if ($detalle['MntTotal'] === null) {
            $montos = ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']));
            $total = $montos->getAsInt('MntExe', 0) + $montos->getAsInt('MntNeto', 0) + $montos->getAsInt('MntIVA', 0);
            foreach ($ivaNoRec as $iva) {
                $total += ArrayReader::fromArray($iva)->getAsInt('MntIVANoRec', 0);
            }
            if (isset($detalle['FctProp'])) {
                $total += $montos->getAsInt('IVAUsoComun', 0);
            }
            if (!empty($detalle['IVARetTotal'])) {
                $total -= $montos->getAsInt('IVARetTotal', 0);
            }
            if (!empty($detalle['IVARetParcial'])) {
                $total -= $montos->getAsInt('IVARetParcial', 0);
            }
            $total += $montos->getAsInt('MntSinCred', 0)
                + $montos->getAsInt('TabPuros', 0)
                + $montos->getAsInt('TabCigarrillos', 0)
                + $montos->getAsInt('TabElaborado', 0)
                + $montos->getAsInt('ImpVehiculo', 0);
            $detalle['MntTotal'] = $total;
        }

        // Si hay monto neto pero MntIVA no fue asignado, declararlo como 0.
        if ($detalle['MntNeto'] !== null && $detalle['MntIVA'] === null) {
            $detalle['MntIVA'] = 0;
        }

        // Si no hay monto neto, quitar campos de IVA.
        if ($detalle['MntNeto'] === null) {
            $detalle['MntNeto'] = $detalle['TasaImp'] = $detalle['MntIVA'] = null;
        }

        // Limpiar código de sucursal si es 0 o vacío.
        if (!$detalle['CdgSIISucur']) {
            $detalle['CdgSIISucur'] = null;
        }

        // Retornar el detalle normalizado.
        return $detalle;
    }

    /**
     * Calcula el IVA retenido del detalle.
     *
     * Se calcula revisando los "otros impuestos" y sumando todos aquellos que
     * son retención de IVA.
     *
     * @param array<string, mixed> $detalle El detalle del libro de compra/venta.
     * @return int El IVA retenido del detalle.
     */
    private function calcularIvaRetenidoDetalle(array $detalle): int
    {
        $otrosImp = ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']))->get('OtrosImp', ValueType::listOf(ValueType::array())) ?? [];
        if ($otrosImp === []) {
            return 0;
        }

        $ivaRetenido = 0;

        $repository = $this->repositoryManager->getRepository(
            ImpuestoAdicionalRetencion::class
        );

        foreach ($otrosImp as $otroImp) {
            $impuesto = $repository->find($otroImp['CodImp'] ?? null);
            $impuesto = ValueCaster::requireObject($impuesto, ImpuestoAdicionalRetencion::class);

            if ($impuesto->getTipo() === 'R') {
                $ivaRetenido += ArrayReader::fromArray($otroImp)->getAsInt('MntImp', 0);
            }
        }

        return $ivaRetenido;
    }

    /**
     * Calcula el porcentaje de la tasa sobre el monto neto del detalle, redondeado.
     *
     * @param array<string, mixed> $detalle
     */
    private function percentOfNet(array $detalle): int
    {
        $row = ArrayReader::fromArray(ArrayCaster::nullify($detalle, [false, '']));

        return (int) round(($row->getAsInt('MntNeto') ?? 0) * (($row->getAsNumber('TasaImp') ?? 0) / 100));
    }

    /**
     * Verifica que los montos y las tasas de una fila sean números.
     *
     * Un valor que no está (`false`, vacío o `null`) no se verifica. Si uno no es
     * un número, el error dice en qué documento y en qué campo está.
     *
     * @param array<array-key, mixed> $row Fila con `TpoDoc` y `NroDoc` del documento.
     * @param array<int, string> $amounts Campos que son montos enteros.
     * @param array<int, string> $rates Campos que son tasas o factores.
     */
    private function assertNumbers(array $row, array $amounts, array $rates): void
    {
        $reader = ArrayReader::fromArray(ArrayCaster::nullify($row, [false, '']), sprintf(
            'document %s/%s',
            is_scalar($row['TpoDoc'] ?? null) ? (string) $row['TpoDoc'] : '?',
            is_scalar($row['NroDoc'] ?? null) ? (string) $row['NroDoc'] : '?'
        ));

        foreach ($amounts as $field) {
            $reader->getAsInt($field);
        }

        foreach ($rates as $field) {
            $reader->getAsNumber($field);
        }
    }
}
