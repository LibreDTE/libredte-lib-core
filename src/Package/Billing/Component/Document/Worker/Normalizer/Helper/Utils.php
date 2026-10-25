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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper;

/**
 * Utilidades que pueden ser usadas en diferentes procesos de normalización y
 * son independientes del resto del documento.
 */
class Utils
{
    /**
     * Redondea valores asociados a un tipo de moneda.
     *
     * Si los valores son en pesos chilenos se redondea sin decimales. Si los
     * valores están en otro tipo de moneda se mantienen los decimales, por
     * defecto se mantienen 4 decimales.
     *
     * @param int|float $amount Valor que se desea redondear.
     * @param string|null|false $currency La moneda en la que está el valor.
     * @param int $decimals Cantidad de decimales a mantener cuando la moneda
     * no es peso chileno.
     * @return int|float Valor redondeado según la moneda y decimales a usar.
     */
    public static function round(
        int|float $amount,
        string|null|false $currency = null,
        int $decimals = 4
    ): int|float {
        return (!$currency || $currency === 'PESO CL')
            ? (int) round($amount)
            : (float) round($amount, $decimals)
        ;
    }

    /**
     * Obtiene el monto neto y el IVA de ese neto a partir de un monto total.
     *
     * NOTE: El IVA obtenido puede no ser el NETO * (TASA / 100). Se calcula el
     * monto neto y luego se obtiene el IVA haciendo la resta entre el total y
     * el neto. Hay casos de borde que generan problemas como:
     *
     *   - BRUTO:   680 => NETO:   571 e IVA:   108 => TOTAL:   679
     *   - BRUTO: 86710 => NETO: 72866 e IVA: 13845 => TOTAL: 86711
     *
     * Estos casos son "normales", pues por aproximaciones "no da".
     *
     * @param int $total Total que representa el monto neto más el IVA.
     * @param int|float|false $tasa Tasa del IVA o `false` si no corresponde.
     * @return array Arreglo con el neto y el IVA en índices 0 y 1.
     */
    public static function calculateNetoIVA($total, int|float|false $tasa): array
    {
        // Si no existe tasa es porque no hay Neto ni IVA (doc exento).
        if ($tasa === 0 || $tasa === false) {
            return [0, 0];
        }

        // Obtener el neto e IVA a partir del total.
        $neto = round($total / (1 + ($tasa / 100)));
        $iva = $total - $neto;

        // Entregar el neto e IVA.
        return [$neto, $iva];
    }
}
