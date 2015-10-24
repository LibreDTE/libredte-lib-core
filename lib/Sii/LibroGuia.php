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
 * Clase que representa el envío de un Libro de Guías de despacho
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-10-02
 */
class LibroGuia
{

    private $detalles = []; ///< Arreglos con el detalle de los DTEs que se reportarán
    private $xml_data; ///< String con el documento XML
    private $caratula; ///< arreglo con la caratula del envío
    private $Firma; ///< objeto de la firma electrónica

    /**
     * Método que agrega un detalle al listado que se enviará
     * @param detalle Arreglo con el resumen del DTE que se desea agregar
     * @return =true si se pudo agregar el detalle o =false si no se agregó por exceder el límite del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    public function agregar(array $detalle, $normalizar = true)
    {
        if ($normalizar)
            $this->normalizarDetalle($detalle);
        $this->detalles[] = $detalle;
        return true;
    }

    /**
     * Método que normaliza un detalle del libro de guías
     * @param detalle Arreglo con el resumen del DTE que se desea agregar
     * @return Arreglo con el detalle normalizado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    private function normalizarDetalle(array &$detalle)
    {
        // agregar nodos (esto para mantener orden del XML)
        $detalle = array_merge([
            'Folio' => false,
            'Anulado' => false,
            'Operacion' => false,
            'TpoOper' => false,
            'FchDoc' => date('Y-m-d'),
            'RUTDoc' => false,
            'RznSoc' => false,
            'MntNeto' => false,
            'TasaImp' => 0,
            'IVA' => 0,
            'MntTotal' => false,
            'MntModificado' => false,
            'TpoDocRef' => false,
            'FolioDocRef' => false,
            'FchDocRef' => false,
        ], $detalle);
        // calcular valores que no se hayan entregado
        if (!$detalle['IVA'] and $detalle['TasaImp'] and $detalle['MntNeto']) {
            $detalle['IVA'] = round($detalle['MntNeto'] * ($detalle['TasaImp']/100));
        }
        // calcular monto total si no se especificó
        if ($detalle['MntTotal']===false) {
            $detalle['MntTotal'] = $detalle['MntNeto'] + $detalle['IVA'];
        }
    }

    /**
     * Método para asignar la caratula
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-24
     */
    public function setCaratula(array $caratula)
    {
        $this->caratula = array_merge([
            'RutEmisorLibro' => false,
            'RutEnvia' => isset($this->Firma) ? $this->Firma->getID() : false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => false,
            'NroResol' => false,
            'TipoLibro' => 'ESPECIAL',
            'TipoEnvio' => 'TOTAL',
            'FolioNotificacion' => null,
        ], $caratula);
        if ($this->caratula['TipoEnvio']=='ESPECIAL')
            $this->caratula['FolioNotificacion'] = null;
        $this->id = 'LIBRO_GUIA_'.str_replace('-', '', $this->caratula['RutEmisorLibro']).'_'.str_replace('-', '', $this->caratula['PeriodoTributario']).'_'.date('U');
    }

    /**
     * Método que entrega el ID del libro
     * @return ID del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Método para asignar la caratula
     * @param Firma Objeto con la firma electrónica
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    public function setFirma(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $this->Firma = $Firma;
    }

    /**
     * Método que realiza el envío del libro IECV al SII
     * @return Track ID del envío o =false si hubo algún problema al enviar el documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    public function enviar()
    {
        // generar XML que se enviará
        if (!$this->xml_data)
            $this->xml_data = $this->generar();
        if (!$this->xml_data) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::LIBROGUIA_ERROR_GENERAR_XML,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::LIBROGUIA_ERROR_GENERAR_XML)
            );
            return false;
        }
        // validar schema del documento antes de enviar
        if (!$this->schemaValidate())
            return false;
        // solicitar token
        $token = Autenticacion::getToken($this->Firma);
        if (!$token)
            return false;
        // enviar DTE
        $result = \sasco\LibreDTE\Sii::enviar($this->caratula['RutEnvia'], $this->caratula['RutEmisorLibro'], $this->xml_data, $token);
        if ($result===false)
            return false;
        if (!is_numeric((string)$result->TRACKID))
            return false;
        return (int)(string)$result->TRACKID;
    }

    /**
     * Método que genera el XML del libro IECV para el envío al SII
     * @param incluirDetalle =true no se incluirá el detalle de los DTEs (sólo se usará para calcular totales)
     * @return XML con el envio del libro de guías de despacho firmado o =false si no se pudo generar o firmar el envío
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    public function generar($incluirDetalle = true)
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data)
            return $this->xml_data;
        // generar XML del envío
        $xmlEnvio = (new \sasco\LibreDTE\XML())->generate([
            'LibroGuia' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte LibroGuia_v10.xsd',
                    'version' => '1.0',
                ],
                'EnvioLibro' => [
                    '@attributes' => [
                        'ID' => $this->id,
                    ],
                    'Caratula' => $this->caratula,
                    'ResumenPeriodo' => $this->getResumenPeriodo(),
                    'Detalle' => $incluirDetalle ? $this->detalles : false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ]
        ])->saveXML();
        // firmar XML del envío y entregar
        $this->xml_data = $this->Firma ? $this->Firma->signXML($xmlEnvio, '#'.$this->id, 'EnvioLibro', true) : $xmlEnvio;
        return $this->xml_data;
    }

    /**
     * Método que obtiene los datos para generar los tags TotalesPeriodo
     * @return Arreglo con los datos para generar los tags TotalesPeriodo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    private function getResumenPeriodo()
    {
        $ResumenPeriodo = [
            'TotFolAnulado' => false,
            'TotGuiaAnulada' => false,
            'TotGuiaVenta' => false,
            'TotMntGuiaVta' => false,
            'TotTraslado' => false,
        ];
        foreach ($this->detalles as &$d) {
            // se contabiliza si la guía está anulada
            if ($d['Anulado']==1 or $d['Anulado']==2) {
                if ($d['Anulado']==1) {
                    $ResumenPeriodo['TotFolAnulado'] = (int)$ResumenPeriodo['TotFolAnulado'] + 1;
                } else {
                    $ResumenPeriodo['TotGuiaAnulada'] = (int)$ResumenPeriodo['TotGuiaAnulada'] + 1;
                }
            }
            // si no está anulado
            else {
                // si es de venta
                if ($d['TpoOper']==1) {
                    $ResumenPeriodo['TotGuiaVenta'] = (int)$ResumenPeriodo['TotGuiaVenta'] + 1;
                    $ResumenPeriodo['TotMntGuiaVta'] = (int)$ResumenPeriodo['TotMntGuiaVta'] + $d['MntTotal'];
                }
                // si no es de venta
                else {
                    if ($ResumenPeriodo['TotTraslado']===false) {
                        $ResumenPeriodo['TotTraslado'] = [];
                    }
                    if (!isset($ResumenPeriodo['TotTraslado'][$d['TpoOper']])) {
                        $ResumenPeriodo['TotTraslado'][$d['TpoOper']] = [
                            'TpoTraslado' => $d['TpoOper'],
                            'CantGuia' => 0,
                            'MntGuia' => 0,
                        ];
                    }
                    $ResumenPeriodo['TotTraslado'][$d['TpoOper']]['CantGuia']++;
                    $ResumenPeriodo['TotTraslado'][$d['TpoOper']]['MntGuia'] += $d['MntTotal'];
                }
            }
        }
        return $ResumenPeriodo;
    }

    /**
     * Método que valida el XML que se genera para la respuesta del envío
     * @return =true si el schema del documento del envío es válido, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-10-02
     */
    public function schemaValidate()
    {
        if (!$this->xml_data) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::LIBROGUIA_FALTA_XML,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::LIBROGUIA_FALTA_XML)
            );
            return null;
        }
        $xsd = dirname(dirname(dirname(__FILE__))).'/schemas/LibroGuia_v10.xsd';
        $this->xml = new \sasco\LibreDTE\XML();
        $this->xml->loadXML($this->xml_data);
        $result = $this->xml->schemaValidate($xsd);
        if (!$result) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::LIBROGUIA_ERROR_SCHEMA,
                \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::LIBROGUIA_ERROR_SCHEMA, implode("\n", $this->xml->getErrors()))
            );
        }
        return $result;
    }

}
