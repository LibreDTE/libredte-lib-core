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
 * Clase que representa el envío de un Consumo de Folios
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-14
 */
class ConsumoFolio extends \sasco\LibreDTE\Sii\Base\Libro
{

    /**
     * Método que agrega un DTE al listado que se enviará
     * @param detalle Arreglo con el resumen del DTE que se desea agregar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
     */
    public function agregar(array $detalle)
    {
        $this->detalles[] = $detalle;
    }

    /**
     * Método para asignar la caratula
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol, etc
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
     */
    public function setCaratula(array $caratula)
    {
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0',
            ],
            'RutEmisor' => false,
            'RutEnvia' => isset($this->Firma) ? $this->Firma->getID() : false,
            'FchResol' => false,
            'NroResol' => false,
            'FchInicio' => $this->getFechaEmisionInicial(),
            'FchFinal' => $this->getFechaEmisionFinal(),
            'Correlativo' => false,
            'SecEnvio' => 1,
            'TmstFirmaEnv' => date('Y-m-d\TH:i:s'),
        ], $caratula);
        $this->id = 'CONSUMO_FOLIO_'.str_replace('-', '', $this->caratula['RutEmisor']).'_'.str_replace('-', '', $this->caratula['FchInicio']).'_'.date('U');
    }

    /**
     * Método que genera el XML del consumo de folios para el envío al SII
     * @return XML con el envio del consumo de folios firmado o =false si no se pudo generar o firmar el envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
     */
    public function generar()
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // generar XML del envío
        $xmlEnvio = (new \sasco\LibreDTE\XML())->generate([
            'ConsumoFolios' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte ConsumoFolio_v10.xsd',
                    'version' => '1.0',
                ],
                'DocumentoConsumoFolios' => [
                    '@attributes' => [
                        'ID' => $this->id,
                    ],
                    'Caratula' => $this->caratula,
                    'Resumen' => $this->getResumen(),
                ],
            ]
        ])->saveXML();
        // firmar XML del envío y entregar
        $this->xml_data = $this->Firma ? $this->Firma->signXML($xmlEnvio, '#'.$this->id, 'DocumentoConsumoFolios', true) : $xmlEnvio;
        return $this->xml_data;
    }

    /**
     * Método que entrega la fecha del primer documento que se está reportando
     * @return Fecha del primer documento que se está reportando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
     */
    public function getFechaEmisionInicial()
    {
        $fecha = '9999-12-31';
        foreach ($this->detalles as &$d) {
            if ($d['FchDoc'] < $fecha)
                $fecha = $d['FchDoc'];
        }
        return $fecha;
    }

    /**
     * Método que entrega la fecha del último documento que se está reportando
     * @return Fecha del último documento que se está reportando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
     */
    public function getFechaEmisionFinal()
    {
        $fecha = '0000-01-01';
        foreach ($this->detalles as &$d) {
            if ($d['FchDoc'] > $fecha)
                $fecha = $d['FchDoc'];
        }
        return $fecha;
    }

    /**
     * Método que obtiene los datos para generar los tags de Resumen del
     * consumo de folios
     * @return Arreglo con los datos para generar los tags Resumen
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
     */
    private function getResumen()
    {
        // si no hay detalles que enviar se entrega falso
        if (!isset($this->detalles[0]))
            return false;
        // si hay detalles generar resumen
        $Resumen = [];
        $RangoUtilizados = [];
        //$RangoAnulados = [];
        foreach ($this->detalles as &$d) {
            // si no existe el tipo de documento se utiliza
            if (!isset($Resumen[$d['TpoDoc']])) {
                $Resumen[$d['TpoDoc']] = [
                    'TipoDocumento' => $d['TpoDoc'],
                    'MntNeto' => false,
                    'MntIva' => false,
                    'TasaIVA' => $d['TasaImp'] ? $d['TasaImp'] : false,
                    'MntExento' => false,
                    'MntTotal' => 0,
                    'FoliosEmitidos' => 0,
                    'FoliosAnulados' => 0,
                    'FoliosUtilizados' => false,
                    'RangoUtilizados' => false,
                    //'RangoAnulados' => false,
                ];
                $RangoUtilizados[$d['TpoDoc']] = [];
                //$RangoAnulados[$d['TpoDoc']] = [];
            }
            // ir agregando al resumen cada detalle
            if ($d['MntNeto']) {
                $Resumen[$d['TpoDoc']]['MntNeto'] += $d['MntNeto'];
                $Resumen[$d['TpoDoc']]['MntIva'] += $d['MntIVA'];
            }
            if ($d['MntExe']) {
                $Resumen[$d['TpoDoc']]['MntExento'] += $d['MntExe'];
            }
            $Resumen[$d['TpoDoc']]['MntTotal'] += $d['MntTotal'];
            $Resumen[$d['TpoDoc']]['FoliosEmitidos']++;
            // ir guardando folios emitidos para luego crear rangos
            $RangoUtilizados[$d['TpoDoc']][] = $d['NroDoc'];
        }
        // ajustes post agregar detalles
        foreach ($Resumen as &$r) {
            // obtener folios utilizados = emitidos + anulados
            $r['FoliosUtilizados'] = $r['FoliosEmitidos'] + $r['FoliosAnulados'];
            $r['RangoUtilizados'] = $this->getRangos($RangoUtilizados[$r['TipoDocumento']]);
        }
        // entregar resumen
        return $Resumen;
    }

    /**
     * Método que determina los rangos de los folios para el resumen del consumo
     * de folios
     * @param folios Arreglo con los folios que se deben generar sus rangos
     * @return Arreglo con cada uno de los rangos de folios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-13
     */
    private function getRangos($folios)
    {
        // crear auxiliar con los folios separados por rangos
        sort($folios);
        $aux = [];
        $inicial = $folios[0];
        $i = $inicial;
        foreach($folios as $f) {
            if ($i!=$f) {
                $inicial = $f;
                $i = $inicial;
            }
            $aux[$inicial][] = $f;
            $i++;
                $rango = [
                    'Inicial' => $f,
                    'Final' => $f,
                ];

        }
        // crear rangos
        $rangos = [];
        foreach ($aux as $folios) {
            $rango = [
                'Inicial' => $folios[0],
                'Final' => $folios[count($folios)-1],
            ];
            // WARNING: de acuerdo a documentación el Final es obligatorio en
            // los rangos de folios utilizados, pero el Final no puede ser igual
            // al Inicial, ¿entonces?
            // en el caso de rangos de folios anulados el final se omite si es
            // igual al inicial ¿así debería ser en el otro caso? pero no pasa
            // por schema
            if ($rango['Inicial']==$rango['Final'])
                unset($rango['Final']);
            $rangos[] = $rango;
        }
        return $rangos;
    }

}
