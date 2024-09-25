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

namespace libredte\lib\Core\Sii\Dte\Documento\Normalization;

use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;

/**
 * Clase con métodos auxiliares para el proceso de normalización de los datos
 * de un documento.
 */
trait UtilsTrait
{
    /**
     * Entrega el tipo de documento que este "builder" puede construir.
     *
     * @return DocumentoTipo
     */
    abstract protected function getTipoDocumento(): DocumentoTipo;

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
    protected function round(
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
     * @param int|false|null $tasa Tasa del IVA.
     * @return array Arreglo con el neto y el IVA en índices 0 y 1.
     */
    protected function calcularNetoIVA($total, int|false|null $tasa = null): array
    {
        // Si no se indicó tasa se usa el valor por defecto para el documento.
        if ($tasa === null) {
            $tasa = $this->getTipoDocumento()->getDefaultTasaIVA();
        }

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
