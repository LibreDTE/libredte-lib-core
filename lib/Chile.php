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

namespace sasco\LibreDTE;

/**
 * Clase para acciones genéricas asociadas a Chile
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-07-29
 */
class Chile
{

    private static $ciudades = [
        'HUECHURABA' => 'Santiago',
        'LA CISTERNA' => 'Santiago',
        'LAS CONDES' => 'Santiago',
        'LO ESPEJO' => 'Santiago',
        'PEÑALOLÉN' => 'Santiago',
        'PUDAHUEL' => 'Santiago',
        'RECOLETA' => 'Santiago',
        'SAN MIGUEL' => 'Santiago',
        'VITACURA' => 'Santiago',
    ]; /// Ciudades de Chile según la comuna

    /**
     * Método que entrega la dirección regional según la comuna que se esté
     * consultando
     * @param comuna de la sucursal del emior o bien código de la sucursal del SII
     * @return Dirección regional del SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-12
     */
    public static function getCiudad($comuna)
    {
        if (!$comuna)
            return false;
        $comuna = mb_strtoupper($comuna, 'UTF-8');
        return isset(self::$ciudades[$comuna]) ? self::$ciudades[$comuna] : false;
    }

}
