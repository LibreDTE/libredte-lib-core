<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sasco\LibreDTE\Sii;

/**
 * Clase para trabajar con los impuestos adicionales
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-03-03
 */
class ImpuestosAdicionales
{

    private static $impuestos = [
        15 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido',
        ],
        19 => [
            'tipo' => 'A',
            'glosa' => 'IVA anticipado harina',
        ],
        34 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido trigo',
        ],
    ]; ///< Datos de impuestos adicionales (A) y retenciones (R)

    /**
     * Indica si el impuesto es adicional o retención
     * @return A: adicional, R: retención y =false no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-03
     */
    public static function getTipo($codigo)
    {
        if (isset(self::$impuestos[$codigo]))
            return self::$impuestos[$codigo]['tipo'];
        return false;
    }

    /**
     * Entrega la glosa del impuesto adicional
     * @return Glosa del impuesto o =false si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-03
     */
    public static function getGlosa($codigo)
    {
        if (isset(self::$impuestos[$codigo]))
            return self::$impuestos[$codigo]['glosa'];
        return 'Impto. cód. '.$codigo;
    }

    /**
     * Método que entrega el monto de impuesto retenido a partir de la
     * información del tag OtrosImp del DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-03
     */
    public static function getRetenido($OtrosImp)
    {
        $retenido = 0;
        foreach ($OtrosImp as $Imp) {
            if (self::getTipo($Imp['CodImp'])=='R') {
                $retenido += $Imp['MntImp'];
            }
        }
        return $retenido;
    }

}
