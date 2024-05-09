<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
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
 * Clase para tests de la clase \libredte\lib\Sii\LibroCompraVenta
 * @version 2017-08-16
 */
class LibroCompraVentaTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Creación libro de compra simplificado sin movimiento
     * @version 2016-02-05
     */
    public function testLibroComprasSimplificadoSinMovimiento()
    {
        $Libro = new \libredte\lib\Sii\LibroCompraVenta(true);
        $Libro->setCaratula([
            'RutEmisorLibro' => '76192083-9',
            'RutEnvia' => '11222333-4',
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => date('Y-m-d'),
            'NroResol' =>  0,
            'TipoOperacion' => 'COMPRA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
        ]);
        $Libro->generar();
        $valid = $Libro->schemaValidate();
        if (!$valid) {
            foreach (\libredte\lib\Log::readAll() as $error)
                echo $error,"\n";
        }
        $this->assertEquals(true, $valid);
    }

    /**
     * Creación libro de venta simplificado sin movimiento
     * @version 2016-02-05
     */
    public function testLibroVentasSimplificadoSinMovimiento()
    {
        $Libro = new \libredte\lib\Sii\LibroCompraVenta(true);
        $Libro->setCaratula([
            'RutEmisorLibro' => '76192083-9',
            'RutEnvia' => '11222333-4',
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => date('Y-m-d'),
            'NroResol' =>  0,
            'TipoOperacion' => 'VENTA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
        ]);
        $Libro->generar();
        $valid = $Libro->schemaValidate();
        if (!$valid) {
            foreach (\libredte\lib\Log::readAll() as $error)
                echo $error,"\n";
        }
        $this->assertEquals(true, $valid);
    }

    /**
     * Creación libro de compra sin movimiento
     * @version 2016-02-05
     */
    public function testLibroComprasSinMovimiento()
    {
        global $_config;
        $Firma = new \libredte\lib\FirmaElectronica($_config['firma']);
        $Libro = new \libredte\lib\Sii\LibroCompraVenta();
        $Libro->setFirma($Firma);
        $Libro->setCaratula([
            'RutEmisorLibro' => '76192083-9',
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => date('Y-m-d'),
            'NroResol' =>  0,
            'TipoOperacion' => 'COMPRA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
        ]);
        $Libro->generar();
        $valid = $Libro->schemaValidate();
        if (!$valid) {
            foreach (\libredte\lib\Log::readAll() as $error)
                echo $error,"\n";
        }
        $this->assertEquals(true, $valid);
    }

    /**
     * Creación libro de venta sin movimiento
     * @version 2016-02-05
     */
    public function testLibroVentasSinMovimiento()
    {
        global $_config;
        $Firma = new \libredte\lib\FirmaElectronica($_config['firma']);
        $Libro = new \libredte\lib\Sii\LibroCompraVenta();
        $Libro->setFirma($Firma);
        $Libro->setCaratula([
            'RutEmisorLibro' => '76192083-9',
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => date('Y-m-d'),
            'NroResol' =>  0,
            'TipoOperacion' => 'VENTA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
        ]);
        $Libro->generar();
        $valid = $Libro->schemaValidate();
        if (!$valid) {
            foreach (\libredte\lib\Log::readAll() as $error)
                echo $error,"\n";
        }
        $this->assertEquals(true, $valid);
    }

    /**
     * Creación libro de compra desde archivo CSV
     * @version 2016-02-05
     */
    public function testLibroComprasCSV()
    {
        global $_config;
        $Firma = new \libredte\lib\FirmaElectronica($_config['firma']);
        $Libro = new \libredte\lib\Sii\LibroCompraVenta();
        $Libro->agregarComprasCSV(dirname(dirname(dirname(__FILE__))).'/examples/libros/libro_compras.csv');
        $Libro->setFirma($Firma);
        $Libro->setCaratula([
            'RutEmisorLibro' => '76192083-9',
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => date('Y-m-d'),
            'NroResol' =>  0,
            'TipoOperacion' => 'COMPRA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
        ]);
        $Libro->generar();
        $valid = $Libro->schemaValidate();
        if (!$valid) {
            foreach (\libredte\lib\Log::readAll() as $error)
                echo $error,"\n";
        }
        $this->assertEquals(true, $valid);
    }

    /**
     * Creación libro de venta desde archivo CSV
     * @version 2016-02-05
     */
    public function testLibroVentasCSV()
    {
        global $_config;
        $Firma = new \libredte\lib\FirmaElectronica($_config['firma']);
        $Libro = new \libredte\lib\Sii\LibroCompraVenta();
        $Libro->agregarVentasCSV(dirname(dirname(dirname(__FILE__))).'/examples/libros/libro_ventas.csv');
        $Libro->setFirma($Firma);
        $Libro->setCaratula([
            'RutEmisorLibro' => '76192083-9',
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => date('Y-m-d'),
            'NroResol' =>  0,
            'TipoOperacion' => 'VENTA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
        ]);
        $Libro->generar();
        $valid = $Libro->schemaValidate();
        if (!$valid) {
            foreach (\libredte\lib\Log::readAll() as $error)
                echo $error,"\n";
        }
        $this->assertEquals(true, $valid);
    }

}
