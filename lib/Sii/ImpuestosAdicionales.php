<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sasco\LibreDTE\Sii;

/**
 * Clase para trabajar con los impuestos adicionales
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-10-18
 */
class ImpuestosAdicionales
{

    private static $impuestos = [
        15 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido',
            'tasa' => 19,
        ],
        17 => [
            'tipo' => 'A',
            'glosa' => 'IVA anticipado faenamiento carne',
            'tasa' => 5,
        ],
        18 => [
            'tipo' => 'A',
            'glosa' => 'IVA anticiado carne',
            'tasa' => 5,
        ],
        19 => [
            'tipo' => 'A',
            'glosa' => 'IVA anticipado harina',
            'tasa' => 12,
        ],
        23 => [
            'tipo' => 'A',
            'glosa' => 'Art 37 letras A, B, C',
            'tasa' => 15,
        ],
        24 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra B',
            'tasa' => 31.5,
        ],
        25 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra C',
            'tasa' => 20.5,
        ],
        26 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra C',
            'tasa' => 20.5,
        ],
        27 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra A',
            'tasa' => 10,
        ],
        271 => [
            'tipo' => 'A',
            'glosa' => 'Art 42 letra A par. 2do',
            'tasa' => 18,
        ],
        30 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido legumbres',
            'tasa' => 10,
        ],
        31 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido silvestres',
            'tasa' => 19,
        ],
        32 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido ganado',
            'tasa' => 8,
        ],
        33 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido madera',
            'tasa' => 8,
        ],
        34 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido trigo',
            'tasa' => 4,
        ],
        36 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido arroz',
            'tasa' => 10,
        ],
        37 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido hidrobiológicas',
            'tasa' => 10,
        ],
        38 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido chatarra',
            'tasa' => 19,
        ],
        39 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido PPA',
            'tasa' => 19,
        ],
        41 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido construcción',
            'tasa' => 19,
        ],
        44 => [
            'tipo' => 'A',
            'glosa' => 'Art 37 letras E, H, I, L',
            'tasa' => 15,
        ],
        45 => [
            'tipo' => 'A',
            'glosa' => 'Art 37 letra J',
            'tasa' => 50,
        ],
        47 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido cartones',
            'tasa' => 19,
        ],
        48 => [
            'tipo' => 'R',
            'glosa' => 'IVA retenido frambuesas y pasas',
            'tasa' => 14,
        ],
        271 => [
            'tipo' => 'A',
            'glosa' => 'Bebidas azucaradas',
            'tasa' => 18,
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
     * @return Glosa del impuesto o glosa estándar si no se encontró una
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
     * Entrega la pasa del impuesto adicional
     * @return Tasa del impuesto o =false si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-19
     */
    public static function getTasa($codigo)
    {
        if (isset(self::$impuestos[$codigo]['tasa']))
            return self::$impuestos[$codigo]['tasa'];
        return false;
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
