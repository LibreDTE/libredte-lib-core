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

namespace sasco\LibreDTE\Sii\Base;

/**
 * Clase base para los libros XML
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-11-07
 */
abstract class Libro extends Envio
{

    protected $detalles = []; ///< Arreglos con los detalles del documento
    protected $resumen = []; ///< resumenes del libro

    /**
     * Método que agrega un detalle al listado que se generará
     * @param detalle Arreglo con los datos del detalle
     * @return =true si se pudo agregar el detalle o =false si hubo cualquier problema
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    abstract public function agregar(array $detalle);

    /**
     * Método que entrega la cantidad de documentos que existen en el detalle
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-10-06
     */
    public function cantidad()
    {
        return count($this->detalles);
    }

    /**
     * Método que entrega el detalle del libro
     * @return Arreglo con los datos de cada detalle del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-11-07
     */
    public function getDetalle()
    {
        return $this->detalles;
    }

}
