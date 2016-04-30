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
 * Clase para trabajar con las tablas de la Aduana
 * Fuentes:
 *  - http://comext.aduana.cl:7001/codigos
 *  - https://www.aduana.cl/compendio-de-normas-anexo-51/aduana/2008-02-18/165942.html
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-04-28
 */
class Aduana
{

    private static $tablas = [
        'CodModVenta' => [
            'glosa' => 'Mod. venta',
            'valor' => [
                1 => 'A firme',
                2 => 'Bajo condición',
                3 => 'En consignación libre',
                4 => 'En consignación con un mínimo a firme',
                9 => 'Sin pago',
            ],
        ],
        'CodClauVenta' => [
            'glosa' => 'Claú. venta',
            'valor' => [
                1 => 'CIF',
                2 => 'CFR',
            ],
        ],
        'TotClauVenta' => 'Total claú.',
        'CodViaTransp' => [
            'glosa' => 'Transporte',
            'valor' => [
                1 => 'Marítima, fluvial y lacustre',
                7 => 'Carretero/terrestre',
            ],
        ],
        'CodPtoEmbarque' => [
            'glosa' => 'Embarque',
            'valor' => [
                906 => 'San Antonio',
                918 => 'Caldera',
            ],
        ],
        'CodPtoDesemb' => [
            'glosa' => 'Desembarq.',
            'valor' => [
                563 => 'Barcelona',
                811 => 'Sidney',
            ],
        ],
        'CodUnidMedTara' => [
            'glosa' => 'U. tara',
            'tabla' => 'unidades',
        ],
        'CodUnidPesoBruto' => [
            'glosa' => 'U. p. bruto',
            'tabla' => 'unidades',
        ],
        'CodUnidPesoNeto' => [
            'glosa' => 'U. p. neto',
            'tabla' => 'unidades',
        ],
        'TotBultos' => 'Total bultos',
        'TipoBultos' => [
            'glosa' => 'Bultos',
            'valor' => [
                13 => 'Rollos',
                80 => 'Pallets',
            ],
        ],
        'CodPaisRecep' => [
            'glosa' => 'P. receptor',
            'tabla' => 'paises',
        ],
        'CodPaisDestin' => [
            'glosa' => 'P. destino',
            'tabla' => 'paises',
        ],
        'unidades' => [
            6 => 'KN',
            9 => 'LT',
            10 => 'U',
            17 => 'PAR',
        ],
        'paises' => [
            406 => 'Australia',
            517 => 'España',
        ],
    ]; ///< Tablas con los datos de la aduana

    /**
     * Entrega la glosa para el campo en la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    public static function getGlosa($tag)
    {
        if (!isset(self::$tablas[$tag]))
            return false;
        return is_array(self::$tablas[$tag]) ? self::$tablas[$tag]['glosa'] : self::$tablas[$tag];
    }

    /**
     * Entrega el valor traducido a partir de la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    public static function getValor($tag, $codigo)
    {
        if (!isset(self::$tablas[$tag]))
            return false;
        if (!is_array(self::$tablas[$tag]))
            return $codigo;
        $tabla = isset(self::$tablas[$tag]['valor']) ? self::$tablas[$tag]['valor'] : self::$tablas[self::$tablas[$tag]['tabla']];
        if ($tag=='TipoBultos') {
            $valor = isset($tabla[$codigo['CodTpoBultos']]) ? $tabla[$codigo['CodTpoBultos']] : $codigo['CodTpoBultos'];
            $valor = $codigo['CantBultos'].' '.$valor;
            if (!empty($codigo['Marcas'])) {
                $valor .= ' ('.$codigo['Marcas'].')';
            } else if (!empty($codigo['IdContainer'])) {
                $valor .= ' ('.$codigo['IdContainer'].')';
            }
        } else {
            $valor = isset($tabla[$codigo]) ? $tabla[$codigo] : $codigo;
        }
        return $valor;
    }

}
