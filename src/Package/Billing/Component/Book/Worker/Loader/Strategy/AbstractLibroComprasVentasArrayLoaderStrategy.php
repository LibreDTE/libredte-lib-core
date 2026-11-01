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

use Derafu\Array\ArrayReader;
use Derafu\Repository\Contract\RepositoryManagerInterface;
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
            'RutEmisorLibro'    => $bag->getEmisor()?->getRut() ?? false,
            'RutEnvia'          => $bag->getCertificate()?->getId() ?? false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol'          => $bag->getBookAuth()['FchResol'] ?? false,
            'NroResol'          => $bag->getBookAuth()['NroResol'] ?? false,
            'TipoOperacion'     => $bag->getTipo() === TipoLibro::VENTAS
                ? TipoOperacion::VENTA->value
                : TipoOperacion::COMPRA->value
            ,
            'TipoLibro'         => 'MENSUAL',
            'TipoEnvio'         => 'TOTAL',
            'FolioNotificacion' => false,
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
            'TpoDoc' => false,
            'Emisor' => false,
            'IndFactCompra' => false,
            'NroDoc' => false,
            'Anulado' => false,
            'Operacion' => false,
            'TpoImp' => 1,
            'TasaImp' => false,
            'NumInt' => false,
            'IndServicio' => false,
            'IndSinCosto' => false,
            'FchDoc' => false,
            'CdgSIISucur' => false,
            'RUTDoc' => false,
            'RznSoc' => false,
            'Extranjero' => false,
            'TpoDocRef' => false,
            'FolioDocRef' => false,
            'MntExe' => false,
            'MntNeto' => false,
            'MntIVA' => false,
            'MntActivoFijo' => false,
            'MntIVAActivoFijo' => false,
            'IVANoRec' => false,
            'IVAUsoComun' => false,
            'IVAFueraPlazo' => false,
            'IVAPropio' => false,
            'IVATerceros' => false,
            'Ley18211' => false,
            'OtrosImp' => false,
            'MntSinCred' => false,
            'IVARetTotal' => false,
            'IVARetParcial' => false,
            'CredEC' => false,
            'DepEnvase' => false,
            'Liquidaciones' => false,
            'MntTotal' => false,
            'IVANoRetenido' => false,
            'MntNoFact' => false,
            'MntPeriodo' => false,
            'PsjNac' => false,
            'PsjInt' => false,
            'TabPuros' => false,
            'TabCigarrillos' => false,
            'TabElaborado' => false,
            'ImpVehiculo' => false,
        ];

        // Normalizar los campos del detalle.
        $detalle = array_merge($default, $detalle);

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
            $detalle['RznSoc'] = mb_substr(self::reader($detalle)->getAsString('RznSoc') ?? '', 0, 50);
        }

        // Calcular IVA de uso común si hay factor de proporcionalidad.
        if (isset($detalle['FctProp'])) {
            if ($detalle['IVAUsoComun'] === false) {
                $detalle['IVAUsoComun'] = $this->percentOfNet($detalle);
            }
        } elseif ($detalle['MntIVA'] === false && !is_array($detalle['IVANoRec']) && $detalle['IVAUsoComun'] === false && $detalle['TasaImp'] && $detalle['MntNeto']) {
            // Calcular IVA si falta y no hay IVA no recuperable.
            $detalle['MntIVA'] = $this->percentOfNet($detalle);
        }

        // Normalizar IVA no recuperable a lista.
        if (!empty($detalle['IVANoRec'])) {
            $detalle['IVANoRec'] = self::toList($detalle['IVANoRec']);
        }

        // Normalizar otros impuestos a lista.
        if (!empty($detalle['OtrosImp'])) {
            $detalle['OtrosImp'] = self::toList($detalle['OtrosImp']);
        }

        // Los montos y las tasas de los impuestos que van en listas.
        foreach (['IVANoRec' => [['MntIVANoRec'], []], 'OtrosImp' => [['MntImp'], ['TasaImp']]] as $field => [$amounts, $rates]) {
            foreach (self::toList($detalle[$field]) as $item) {
                $this->assertNumbers(array_merge(['TpoDoc' => $detalle['TpoDoc'], 'NroDoc' => $detalle['NroDoc']], $item), $amounts, $rates);
            }
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
                $detalle['IVANoRetenido'] = self::intOf($detalle, 'MntIVA') - $ivaRetenido;
            }
        }

        // Calcular monto total si falta.
        if ($detalle['MntTotal'] === false) {
            $total = self::intOf($detalle, 'MntExe') + self::intOf($detalle, 'MntNeto') + self::intOf($detalle, 'MntIVA');
            foreach (self::toList($detalle['IVANoRec']) as $iva) {
                $total += self::intOf($iva, 'MntIVANoRec');
            }
            if (isset($detalle['FctProp'])) {
                $total += self::intOf($detalle, 'IVAUsoComun');
            }
            if (!empty($detalle['IVARetTotal'])) {
                $total -= self::intOf($detalle, 'IVARetTotal');
            }
            if (!empty($detalle['IVARetParcial'])) {
                $total -= self::intOf($detalle, 'IVARetParcial');
            }
            $total += self::intOf($detalle, 'MntSinCred')
                + self::intOf($detalle, 'TabPuros')
                + self::intOf($detalle, 'TabCigarrillos')
                + self::intOf($detalle, 'TabElaborado')
                + self::intOf($detalle, 'ImpVehiculo');
            $detalle['MntTotal'] = $total;
        }

        // Si hay monto neto pero MntIVA no fue asignado, declararlo como 0.
        if ($detalle['MntNeto'] !== false && $detalle['MntIVA'] === false) {
            $detalle['MntIVA'] = 0;
        }

        // Si no hay monto neto, quitar campos de IVA.
        if ($detalle['MntNeto'] === false) {
            $detalle['MntNeto'] = $detalle['TasaImp'] = $detalle['MntIVA'] = false;
        }

        // Limpiar código de sucursal si es 0 o vacío.
        if (!$detalle['CdgSIISucur']) {
            $detalle['CdgSIISucur'] = false;
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
        $otrosImp = self::toList($detalle['OtrosImp'] ?? []);
        if ($otrosImp === []) {
            return 0;
        }

        $ivaRetenido = 0;

        $repository = $this->repositoryManager->getRepository(
            ImpuestoAdicionalRetencion::class
        );

        foreach ($otrosImp as $otroImp) {
            $impuesto = $repository->find($otroImp['CodImp'] ?? null);
            assert($impuesto instanceof ImpuestoAdicionalRetencion);

            if ($impuesto->getTipo() === 'R') {
                $ivaRetenido += self::intOf($otroImp, 'MntImp');
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
        $row = self::reader($detalle);

        return (int) round(($row->getAsInt('MntNeto') ?? 0) * (($row->getAsNumber('TasaImp') ?? 0) / 100));
    }

    /**
     * Entrega un monto de la fila como número entero: 0 si no hay un valor.
     *
     * @param array<array-key, mixed> $row
     */
    private static function intOf(array $row, string $field): int
    {
        return self::reader($row)->getAsInt($field) ?? 0;
    }

    /**
     * Entrega un valor que puede ser un elemento o una lista de elementos como
     * una lista de elementos (cada uno un arreglo). Lo que no está o no es un
     * arreglo da una lista vacía.
     *
     * @return list<array<array-key, mixed>>
     */
    private static function toList(mixed $value): array
    {
        if (!is_array($value) || $value === []) {
            return [];
        }

        $list = [];
        foreach (array_is_list($value) ? $value : [$value] as $item) {
            if (is_array($item)) {
                $list[] = $item;
            }
        }

        return $list;
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
        $reader = self::reader($row, sprintf(
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

    /**
     * Entrega un lector de la fila. En el detalle `false` y el texto vacío son lo
     * que se pone cuando no hay un valor.
     *
     * @param array<array-key, mixed> $row
     */
    private static function reader(array $row, ?string $source = null): ArrayReader
    {
        return ArrayReader::fromArray(
            array_map(static fn (mixed $value): mixed => $value === false || $value === '' ? null : $value, $row),
            $source
        );
    }
}
