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
 * @version 2016-03-14
 */
class ImpuestosAdicionales
{

    private static $impuestos = [
        15 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido',
        ],
        17 => [
            'tipo' => 'A',
            'glosa' => 'IVA anticipado faenamiento carne',
        ],
        18 => [
            'tipo' => 'A',
            'glosa' => 'IVA anticiado carne',
        ],
        19 => [
            'tipo' => 'A',
            'glosa' => 'IVA anticipado harina',
        ],
        23 => [
            'tipo' => 'A',
            'glosa' => 'Art 37 letras A, B, C',
        ],
        24 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra B',
        ],
        25 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra C',
        ],
        26 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra C',
        ],
        27 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra A',
        ],
        271 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra A par. 2do',
        ],
        30 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido legumbres',
        ],
        31 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido silvestres',
        ],
        32 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido ganado',
        ],
        33 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido madera',
        ],
        34 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido trigo',
        ],
        36 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido arroz',
        ],
        37 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido hidrobiológicas',
        ],
        38 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido chatarra',
        ],
        39 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido PPA',
        ],
        41 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido construcción',
        ],
        44 => [
            'tipo' => 'A',
            'glosa' => 'Art 37 letras E, H, I, L',
        ],
        45 => [
            'tipo' => 'A',
            'glosa' => 'Art 37 letra J',
        ],
        47 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido cartones',
        ],
        48 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido frambuesas y pasas',
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
