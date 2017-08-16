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

/**
 * Clase para tests de la clase \sasco\LibreDTE\XML
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-08-16
 */
class XMLTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Se verifica que un arreglo se pueda convertir a XML y luego a arreglo
     * obteniendo el arreglo original
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-28
     */
    public function testArrayXMLArray()
    {
        $array = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 33,
                    'Folio' => 1,
                ],
                'Emisor' => [
                    'RUTEmisor' => '76192083-9',
                    'RznSoc' => 'SASCO SpA',
                    'GiroEmis' => 'Servicios integrales de informática',
                    'Acteco' => 726000,
                    'DirOrigen' => 'Santiago',
                    'CmnaOrigen' => 'Santiago',
                ],
                'Receptor' => [
                    'RUTRecep' => '60803000-K',
                    'RznSocRecep' => 'Servicio de Impuestos Internos',
                    'GiroRecep' => 'Gobierno',
                    'DirRecep' => 'Alonso Ovalle 680',
                    'CmnaRecep' => 'Santiago',
                ],
            ],
        ];
        $XML = new \sasco\LibreDTE\XML();
        $XML->generate($array);
        $xml = $XML->C14N();
        $XML2 = new \sasco\LibreDTE\XML();
        $XML2->loadXML($xml);
        $this->assertEquals($array, $XML2->toArray());
    }

}
